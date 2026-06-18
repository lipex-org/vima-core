<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\User\Entities\UserPermission;

class ArrayUserPermissionRepository implements UserPermissionRepositoryInterface
{
    private array $links = [];
    private int $nextId = 1;

    public function findByUserId(int|string $userId): array
    {
        return array_values(array_filter($this->links, fn(UserPermission $up) => $up->userId == $userId));
    }

    public function add(UserPermission $permission): void
    {
        if ($permission->id === null) {
            $permission->id = $this->nextId++;
        }
        
        foreach ($this->links as $id => $link) {
            if ($link->userId == $permission->userId && $link->permissionId == $permission->permissionId) {
                unset($this->links[$id]);
            }
        }
        
        $this->links[$permission->id] = clone $permission;
    }

    public function remove(UserPermission $permission): void
    {
        foreach ($this->links as $id => $link) {
            if ($link->userId == $permission->userId && $link->permissionId == $permission->permissionId) {
                unset($this->links[$id]);
                return;
            }
        }
    }
}
