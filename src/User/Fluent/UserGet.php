<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org/vima-core>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\User\Fluent;

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Cache\Contracts\CacheInterface;
use Vima\Core\Cache\Services\CacheVersionManager;
use Vima\Core\Support\Utils\Utils;
use function Vima\Core\resolve;

class UserGet
{
    private ?CacheVersionManager $versionManager = null;

    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserRoleRepositoryInterface $userRoles,
        private UserPermissionRepositoryInterface $userPermissions,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies,
        private UserIsDenied $userIsDenied,
        private VimaConfig $config,
        private ?CacheInterface $cache = null,
        ?CacheVersionManager $versionManager = null
    ) {
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
        if ($this->versionManager === null && $this->cache !== null) {
            $this->versionManager = new CacheVersionManager($this->cache, $this->config);
        }
    }

    public function denies(): UserGetDenies
    {
        return new UserGetDenies($this->userId, $this->userDenies, $this->userRoleDenies);
    }

    public function permissions(): UserGetPermissions
    {
        return new UserGetPermissions(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userPermissions,
            $this
        );
    }

    public function roles(bool $resolve = false): array
    {
        $l1Key = "u_{$this->userId}_roles_" . ($resolve ? '1' : '0');
        if (CacheVersionManager::hasL1($l1Key)) {
            return CacheVersionManager::getL1($l1Key);
        }

        $cacheActive = $this->versionManager !== null && $this->versionManager->isCacheEnabled();

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'roles', [], $resolve ? 'res' : 'raw');
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cached = $cache->get($cacheKey);
                if (is_array($cached)) {
                    CacheVersionManager::setL1($l1Key, $cached);
                    return $cached;
                }
            }
        }

        $userRoles = $this->userRoles->getRolesForUser($this->userId);
        $roles = [];
        foreach ($userRoles as $ur) {
            $role = $this->roleService->find($ur->roleId, $resolve);
            if ($role) {
                $role->context = array_merge($role->context ?? [], $ur->context ?? []);
                $roles[] = $role;
            }
        }

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'roles', [], $resolve ? 'res' : 'raw');
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cache->set($cacheKey, $roles, $this->versionManager->getTTL());
            }
        }

        CacheVersionManager::setL1($l1Key, $roles);

        return $roles;
    }

    public function compiled(array $context = []): array
    {
        $ctxHash = !empty($context) ? substr(md5(json_encode($context)), 0, 8) : 'all';
        $l1Key = "u_{$this->userId}_compiled_{$ctxHash}";

        if (CacheVersionManager::hasL1($l1Key)) {
            return CacheVersionManager::getL1($l1Key);
        }

        $roles = $this->roles(false);
        $roleIds = array_filter(array_map(fn($r) => $r->id, $roles));

        $cacheActive = $this->versionManager !== null && $this->versionManager->isCacheEnabled();

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'compiled', $roleIds, $ctxHash);
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cached = $cache->get($cacheKey);
                if (is_array($cached)) {
                    CacheVersionManager::setL1($l1Key, $cached);
                    return $cached;
                }
            }
        }

        $validRoles = [];
        foreach ($roles as $r) {
            if ($this->userIsDenied->role($r)) {
                continue;
            }
            $validRoles[] = $r;
        }

        $tenantId = $context['tenant_id'] ?? null;
        if ($tenantId !== null) {
            $expectedNamespace = 'tenant_' . $tenantId;
            $validRoles = array_filter($validRoles, function ($role) use ($expectedNamespace) {
                return $role->namespace === null || $role->namespace === $expectedNamespace;
            });
        }

        if (!empty($context)) {
            $validRoles = array_filter($validRoles, function ($r) use ($context) {
                foreach ($context as $k => $v) {
                    if (isset($r->context[$k]) && $r->context[$k] != $v) {
                        return false;
                    }
                }
                return true;
            });
        }

        $compiled = [];

        foreach ($validRoles as $role) {
            $rolePerms = $this->roleService->role($role)->permissions()->all();
            foreach ($rolePerms as $perm) {
                $fullName = $perm->getFullName();
                if (!isset($compiled[$fullName])) {
                    $compiled[$fullName] = [];
                }
                $compiled[$fullName][] = $perm->constraints ?? [];
            }
        }

        foreach ($this->permissions()->direct() as $dp) {
            $fullName = $dp->getFullName();
            if (!isset($compiled[$fullName])) {
                $compiled[$fullName] = [];
            }
            $compiled[$fullName][] = $dp->constraints ?? [];
        }

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'compiled', $roleIds, $ctxHash);
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cache->set($cacheKey, $compiled, $this->versionManager->getTTL());
            }
        }

        CacheVersionManager::setL1($l1Key, $compiled);

        return $compiled;
    }

    /**
     * Return a flat dictionary of permission names to boolean values for the user.
     * Perfect for Inertia / frontend state.
     *
     * @param string[] $filter Optional list of permission names to evaluate. If empty, evaluates all system permissions.
     * @param array $context Contextual parameters for evaluation.
     * @return array<string, bool>
     */
    public function matrix(array $filter = [], array $context = []): array
    {
        $filterHash = !empty($filter) ? substr(md5(json_encode($filter)), 0, 8) : 'all';
        $ctxHash = !empty($context) ? substr(md5(json_encode($context)), 0, 8) : 'all';
        $l1Key = "u_{$this->userId}_matrix_{$filterHash}_{$ctxHash}";

        if (CacheVersionManager::hasL1($l1Key)) {
            return CacheVersionManager::getL1($l1Key);
        }

        $roles = $this->roles(false);
        $roleIds = array_filter(array_map(fn($r) => $r->id, $roles));

        $cacheActive = $this->versionManager !== null && $this->versionManager->isCacheEnabled();

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'matrix', $roleIds, "{$filterHash}_{$ctxHash}");
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cached = $cache->get($cacheKey);
                if (is_array($cached)) {
                    CacheVersionManager::setL1($l1Key, $cached);
                    return $cached;
                }
            }
        }

        // Determine permissions to check
        if (empty($filter)) {
            $allSystemPerms = $this->permissionService->all();
            $permNames = array_map(fn($p) => $p->getFullName(), $allSystemPerms);
            $compiledKeys = array_keys($this->compiled($context));
            $permNames = array_values(array_unique(array_merge($permNames, $compiledKeys)));
        } else {
            $permNames = $filter;
        }

        $compiled = $this->compiled($context);
        $matrix = [];

        $checkConstraints = function (?array $constraintsList) use ($context) {
            if (empty($constraintsList)) {
                return true;
            }
            foreach ($constraintsList as $constraints) {
                if (empty($constraints)) {
                    return true;
                }
                $match = true;
                foreach ($constraints as $k => $v) {
                    if (!isset($context[$k]) || $context[$k] != $v) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    return true;
                }
            }
            return false;
        };

        foreach ($permNames as $permission) {
            // Check direct deny
            if ($this->userIsDenied->permission($permission)) {
                $matrix[$permission] = false;
                continue;
            }

            [$namespace, $permName] = Utils::resolveNamespace($permission);
            $fullName = $namespace ? "{$namespace}:{$permName}" : $permName;

            $hasPerm = false;

            // 1. Direct exact match in compiled
            if (isset($compiled[$fullName])) {
                if (empty($context) || $checkConstraints($compiled[$fullName])) {
                    $hasPerm = true;
                }
            }

            // 2. Wildcard match in compiled
            if (!$hasPerm) {
                foreach ($compiled as $comp => $constraintsList) {
                    if (str_ends_with($comp, '*')) {
                        $prefix = rtrim($comp, '*');
                        if (str_starts_with($fullName, $prefix)) {
                            if (empty($context) || $checkConstraints($constraintsList)) {
                                $hasPerm = true;
                                break;
                            }
                        }
                    }
                }
            }

            $matrix[$permission] = $hasPerm;
        }

        if ($cacheActive) {
            $cacheKey = $this->versionManager->buildUserKey($this->userId, 'matrix', $roleIds, "{$filterHash}_{$ctxHash}");
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cache->set($cacheKey, $matrix, $this->versionManager->getTTL());
            }
        }

        CacheVersionManager::setL1($l1Key, $matrix);

        return $matrix;
    }
}
