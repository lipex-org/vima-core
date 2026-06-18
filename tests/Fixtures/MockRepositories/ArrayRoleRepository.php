<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use Vima\Core\Role\Entities\Role;

class ArrayRoleRepository implements RoleRepositoryInterface
{
    private array $roles = [];
    private int $nextId = 1;

    public function findByName(string $name): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->getFullName() === $name) {
                return $role;
            }
        }
        return null;
    }

    public function findById(int|string $id): ?Role
    {
        return $this->roles[$id] ?? null;
    }

    public function all(?string $namespace = null): array
    {
        if ($namespace === null) {
            return array_values($this->roles);
        }

        return array_values(array_filter($this->roles, fn(Role $r) => $r->namespace === $namespace));
    }

    public function save(Role $role): Role
    {
        if ($role->id === null) {
            $role->id = $this->nextId++;
        }
        $this->roles[$role->id] = clone $role;
        return $this->roles[$role->id];
    }

    public function delete(Role $role): void
    {
        if ($role->id !== null) {
            unset($this->roles[$role->id]);
        }
    }

    public function deleteAll(): void
    {
        $this->roles = [];
        $this->nextId = 1;
    }
}
