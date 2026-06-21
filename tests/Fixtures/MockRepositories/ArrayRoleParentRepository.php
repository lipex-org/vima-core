<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Entities\RoleParent;

class ArrayRoleParentRepository implements RoleParentRepositoryInterface
{
    private array $links = [];
    private int $nextId = 1;

    public function assign(RoleParent $relationship): void
    {
        if ($relationship->id === null) {
            $relationship->id = $this->nextId++;
        }
        foreach ($this->links as $link) {
            if ($link->roleId == $relationship->roleId && $link->parentId == $relationship->parentId) {
                return;
            }
        }
        $this->links[$relationship->id] = clone $relationship;
    }

    public function remove(RoleParent $relationship): void
    {
        foreach ($this->links as $id => $link) {
            if ($link->roleId == $relationship->roleId && $link->parentId == $relationship->parentId) {
                unset($this->links[$id]);
                return;
            }
        }
    }

    public function clearParents(Role $role): void
    {
        foreach ($this->links as $id => $link) {
            if ($link->roleId == $role->id) {
                unset($this->links[$id]);
            }
        }
    }

    public function getParents(Role $role): array
    {
        return array_values(array_filter($this->links, fn(RoleParent $rp) => $rp->roleId == $role->id));
    }

    public function getChildren(Role $role): array
    {
        return array_values(array_filter($this->links, fn(RoleParent $rp) => $rp->parentId == $role->id));
    }

    public function deleteAll(): void
    {
        $this->links = [];
        $this->nextId = 1;
    }
}
