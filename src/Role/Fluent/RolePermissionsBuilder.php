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

class RolePermissionsBuilder
{

    private PermissionService $permissionService;
    public function __construct(
        private Role $role,
        private RolePermissionRepositoryInterface $rolePermissions,
        private EventDispatcherInterface $dispatcher
    ) {
        $this->permissionService = resolve(PermissionService::class);
    }

    public function add(string|Permission $permission, array $constraints = []): self
    {
        $p = ($permission instanceof Permission && $permission->id !== null) ? $permission : $this->permissionService->find($permission);

        if (!$p) {
            throw new PermissionNotFoundException('Permission is non-existent');
        }

        $this->rolePermissions->assign(new RolePermission(
            roleId: $this->role->id,
            permissionId: $p->id,
            constraints: $constraints
        ));
        return $this;
    }

    public function remove(string|Permission $permission): self
    {
        $p = $this->permissionService->find($permission);

        if (!$p) {
            throw new PermissionNotFoundException('Permission is non-existent');
        }

        $this->rolePermissions->revoke(new RolePermission(
            roleId: $this->role->id,
            permissionId: $p->id
        ));
        return $this;
    }

    /**
     * Gets all permissions for the role including inherited permissions
     * @return Permission[]
     */
    public function all(): array
    {
        return $this->resolvePerms($this->role);
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
