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

namespace Vima\Core\Role\Fluent;

use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Permission\Exceptions\PermissionNotFoundException;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Entities\RolePermission;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Role\Services\RoleService;
use function Vima\Core\resolve;

use Vima\Core\Cache\Services\CacheVersionManager;
use Vima\Core\Events\DomainEvent;

class RolePermissionsBuilder
{
    private PermissionService $permissionService;
    private ?CacheVersionManager $versionManager = null;

    public function __construct(
        private Role $role,
        private RolePermissionRepositoryInterface $rolePermissions,
        private EventDispatcherInterface $dispatcher,
        ?CacheVersionManager $versionManager = null
    ) {
        $this->permissionService = resolve(PermissionService::class);
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
    }

    public function add(string|Permission|array $permission, array $constraints = []): self
    {
        if (is_array($permission)) {
            foreach ($permission as $key => $value) {
                if (is_string($key)) {
                    $this->add($key, is_array($value) ? $value : $constraints);
                } else {
                    $this->add($value, $constraints);
                }
            }
            return $this;
        }

        $p = ($permission instanceof Permission && $permission->id !== null) ? $permission : $this->permissionService->find($permission);

        if (!$p) {
            throw new PermissionNotFoundException('Permission is non-existent');
        }

        $this->rolePermissions->assign(new RolePermission(
            roleId: $this->role->id,
            permissionId: $p->id,
            constraints: $constraints
        ));

        if ($this->role->id !== null && $this->versionManager !== null) {
            $this->versionManager->bumpRoleEpoch($this->role->id);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.role.permission_added', [
            'role' => $this->role,
            'permission' => $p,
            'roleId' => $this->role->id,
            'permissionId' => $p->id
        ]));

        return $this;
    }

    public function remove(string|Permission|array $permission): self
    {
        if (is_array($permission)) {
            foreach ($permission as $p) {
                $this->remove($p);
            }
            return $this;
        }

        $p = $this->permissionService->find($permission);

        if (!$p) {
            throw new PermissionNotFoundException('Permission is non-existent');
        }

        $this->rolePermissions->revoke(new RolePermission(
            roleId: $this->role->id,
            permissionId: $p->id
        ));

        if ($this->role->id !== null && $this->versionManager !== null) {
            $this->versionManager->bumpRoleEpoch($this->role->id);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.role.permission_removed', [
            'role' => $this->role,
            'permission' => $p,
            'roleId' => $this->role->id,
            'permissionId' => $p->id
        ]));

        return $this;
    }

    /**
     * Gets all permissions for the role including inherited permissions
     * @return Permission[]
     */
    public function all(): array
    {
        $roleId = $this->role->id;
        $l1Key = $roleId !== null ? "role_{$roleId}_perms" : null;

        if ($l1Key !== null && CacheVersionManager::hasL1($l1Key)) {
            return CacheVersionManager::getL1($l1Key);
        }

        if ($roleId !== null && $this->versionManager !== null && $this->versionManager->isCacheEnabled()) {
            $cacheKey = $this->versionManager->buildRoleKey($roleId);
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cached = $cache->get($cacheKey);
                if (is_array($cached)) {
                    if ($l1Key !== null) {
                        CacheVersionManager::setL1($l1Key, $cached);
                    }
                    return $cached;
                }
            }
        }

        $resolved = $this->resolvePerms($this->role);

        if ($roleId !== null && $this->versionManager !== null && $this->versionManager->isCacheEnabled()) {
            $cacheKey = $this->versionManager->buildRoleKey($roleId);
            $cache = $this->versionManager->getCache();
            if ($cache !== null) {
                $cache->set($cacheKey, $resolved, $this->versionManager->getTTL());
            }
        }

        if ($l1Key !== null) {
            CacheVersionManager::setL1($l1Key, $resolved);
        }

        return $resolved;
    }

    /**
     * Returns raw role-permission pairs
     * @return RolePermission[]
     */
    public function raw(): array
    {
        return $this->rolePermissions->getRolePermissions($this->role);
    }

    private function resolvePerms(string|Role $role, array &$visited = []): array
    {
        /**
         * @var RoleService
         */
        $roleService = resolve(RoleService::class);
        /**
         * @var RoleParentRepositoryInterface
         */
        $roleParents = resolve(RoleParentRepositoryInterface::class);

        $roleEntity = $roleService->find($role);
        if (!$roleEntity) {
            return [];
        }

        $roleKey = $roleEntity->getFullName();
        if (in_array($roleKey, $visited)) {
            return [];
        }
        $visited[] = $roleKey;

        $perms = [];
        $permissionService = resolve(PermissionService::class);

        $rolePerms = $this->rolePermissions->getRolePermissions($roleEntity);
        foreach ($rolePerms as $rp) {
            $perm = $permissionService->find($rp->permissionId);
            if ($perm) {
                $p = clone $perm;
                $p->constraints = $rp->constraints ?? [];
                $perms[] = $p;
            }
        }

        $parents = $roleParents->getParents($roleEntity);
        foreach ($parents as $parent) {
            $perms = array_merge($perms, $this->resolvePerms((string) $parent->parentId, $visited));
        }

        return $perms;
    }
}
