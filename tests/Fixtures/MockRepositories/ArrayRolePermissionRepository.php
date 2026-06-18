<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Entities\RolePermission;

class ArrayRolePermissionRepository implements RolePermissionRepositoryInterface
{
    private array $links = [];
    private int $nextId = 1;

    public function getRolePermissions(Role $role): array
    {
        return array_values(array_filter($this->links, fn(RolePermission $rp) => $rp->roleId == $role->id));
    }

    public function getPermissionRoles(Permission $permission): array
    {
        return array_values(array_filter($this->links, fn(RolePermission $rp) => $rp->permissionId == $permission->id));
    }

    public function all(): array
    {
        return array_values($this->links);
    }

    public function assign(RolePermission $permission): void
    {
        if ($permission->id === null) {
            $permission->id = $this->nextId++;
        }
        // Avoid exact duplicates
        foreach ($this->links as $link) {
            if ($link->roleId == $permission->roleId && $link->permissionId == $permission->permissionId) {
                return; // Already assigned
            }
        }
        $this->links[$permission->id] = clone $permission;
    }

    public function revoke(RolePermission $permission): void
    {
        foreach ($this->links as $id => $link) {
            if ($link->roleId == $permission->roleId && $link->permissionId == $permission->permissionId) {
                unset($this->links[$id]);
                return;
            }
        }
    }

    public function deleteAll(): void
    {
        $this->links = [];
        $this->nextId = 1;
    }
}
