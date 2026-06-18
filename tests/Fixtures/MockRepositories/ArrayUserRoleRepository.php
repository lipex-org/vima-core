<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Entities\UserRole;

class ArrayUserRoleRepository implements UserRoleRepositoryInterface
{
    private array $links = [];
    private int $nextId = 1;

    public function getRolesForUser(int|string $userId): array
    {
        return array_values(array_filter($this->links, fn(UserRole $ur) => $ur->userId == $userId));
    }

    public function assign(UserRole $userRole): void
    {
        if ($userRole->id === null) {
            $userRole->id = $this->nextId++;
        }
        
        // Remove existing assignment if replacing (to simulate replace behavior usually expected)
        foreach ($this->links as $id => $link) {
            if ($link->userId == $userRole->userId && $link->roleId == $userRole->roleId) {
                unset($this->links[$id]);
            }
        }
        
        $this->links[$userRole->id] = clone $userRole;
    }

    public function revoke(UserRole $userRole): void
    {
        foreach ($this->links as $id => $link) {
            if ($link->userId == $userRole->userId && $link->roleId == $userRole->roleId) {
                unset($this->links[$id]);
                return;
            }
        }
    }
}
