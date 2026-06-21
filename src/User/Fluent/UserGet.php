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

class UserGet
{
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
        private ?CacheInterface $cache = null
    ) {
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
        $cacheActive = $this->config->cacheEnabled && $this->cache !== null;
        $prefix = rtrim($this->config->cachePrefix, '_:');
        $cacheKey = $prefix . ':user:' . $this->userId . ':roles';

        if ($cacheActive) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $userRoles = $this->userRoles->getRolesForUser($this->userId);
        $roles = [];
        foreach ($userRoles as $ur) {
            $role = $this->roleService->find($ur->roleId, $resolve); // Needs resolving in full implement
            if ($role) {
                // To keep it simple, we just return the role. If $resolve is true, 
                // the RoleService would resolve parents/permissions.
                $role->context = array_merge($role->context ?? [], $ur->context ?? []);
                $roles[] = $role;
            }
        }

        if ($cacheActive) {
            $this->cache->set($cacheKey, $roles, $this->config->cacheTTL);
        }

        return $roles;
    }

    public function compiled(array $context = []): array
    {
        $cacheActive = $this->config->cacheEnabled && $this->cache !== null;
        $prefix = rtrim($this->config->cachePrefix, '_:');
        $cacheKey = $prefix . ':user:' . $this->userId . ':permissions';

        if ($cacheActive) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $roles = $this->roles(true);
        $validRoles = [];

        foreach ($roles as $r) {
            if ($this->userIsDenied->role($r)) {
                continue;
            }
            $validRoles[] = $r;
        }

        $compiled = [];

        foreach ($validRoles as $role) {
            // Need the full list of inherited permissions
            $rolePerms = $this->roleService->role($role)->permissions()->all();
            foreach ($rolePerms as $perm) {
                $fullName = ($perm->namespace ? $perm->namespace . ':' : '') . $perm->name;
                if (!isset($compiled[$fullName])) {
                    $compiled[$fullName] = [];
                }
                $compiled[$fullName][] = $perm->constraints ?? [];
            }
        }

        foreach ($this->permissions()->direct() as $dp) {
            $fullName = ($dp->namespace ? $dp->namespace . ':' : '') . $dp->name;
            if (!isset($compiled[$fullName])) {
                $compiled[$fullName] = [];
            }
            $compiled[$fullName][] = $dp->constraints ?? [];
        }

        if ($cacheActive) {
            $this->cache->set($cacheKey, $compiled, $this->config->cacheTTL);
        }

        return $compiled;
    }
}
