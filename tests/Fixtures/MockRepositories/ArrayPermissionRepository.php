<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\Permission\Contracts\PermissionRepositoryInterface;
use Vima\Core\Permission\Entities\Permission;

class ArrayPermissionRepository implements PermissionRepositoryInterface
{
    private array $permissions = [];
    private int $nextId = 1;

    public function findByName(string $name): ?Permission
    {
        foreach ($this->permissions as $perm) {
            if ($perm->getFullName() === $name) {
                return $perm;
            }
        }
        return null;
    }

    public function findById(int|string $id): ?Permission
    {
        return $this->permissions[$id] ?? null;
    }

    public function all(?string $namespace = null): array
    {
        if ($namespace === null) {
            return array_values($this->permissions);
        }
        return array_values(array_filter($this->permissions, fn(Permission $p) => $p->namespace === $namespace));
    }

    public function save(Permission $permission): Permission
    {
        if ($permission->id === null) {
            $permission->id = $this->nextId++;
        }
        $this->permissions[$permission->id] = clone $permission;
        return $this->permissions[$permission->id];
    }

    public function delete(Permission $permission): void
    {
        if ($permission->id !== null) {
            unset($this->permissions[$permission->id]);
        }
    }

    public function deleteAll(): void
    {
        $this->permissions = [];
        $this->nextId = 1;
    }
}
