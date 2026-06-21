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

namespace Vima\Core\Role\Services;

use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\DomainEvent;
use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Fluent\RoleResource;
use Vima\Core\Support\Utils\Utils;
use function Jengo\Base\Support\arr;

/**
 * Class RoleService
 * 
 * Operational service for managing roles and their relationships.
 */
class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private RolePermissionRepositoryInterface $rolePermissions,
        private RoleParentRepositoryInterface $roleParents,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function role(int|string|Role $role): RoleResource
    {
        $roleEntity = $this->find($role);

        return new RoleResource(
            $roleEntity ?? $role,
            $this,
            $this->rolePermissions,
            $this->roleParents,
            $this->dispatcher
        );
    }

    public function find(int|string|Role $role, bool $resolve = false): ?Role
    {
        $id = null;
        $name = null;
        $namespace = null;

        if (is_int($role) || (is_string($role) && ctype_digit($role))) {
            $id = (int) $role;
        } elseif (is_string($role)) {
            [$namespace, $name] = Utils::resolveNamespace($role);
        } else {
            $id = $role->id;
            $name = $role->name;
            $namespace = $role->namespace;
        }

        $fullName = $namespace ? "{$namespace}:{$name}" : $name;

        $foundRole = null;
        if ($id) {
            $foundRole = $this->roles->findById($id);
        }

        if (!$foundRole) {
            $foundRole = $this->roles->findByName($fullName);
        }

        if ($resolve && $foundRole) {
            return $this->resolve($foundRole);
        }

        return $foundRole;
    }

    public function save(Role $role): Role
    {
        $isNew = ($role->id === null);
        $saved = $this->roles->save($role);
        $eventName = $isNew ? 'vima.role.created' : 'vima.role.updated';
        $this->dispatcher->dispatch(new DomainEvent($eventName, ['role' => $saved]));
        return $saved;
    }

    public function delete(Role $role): void
    {
        $this->roles->delete($role);
        $this->dispatcher->dispatch(new DomainEvent('vima.role.deleted', ['role' => $role]));
    }

    /**
     * @param string|null $namespace
     * @return Role[]
     */
    public function all(?string $namespace = null, bool $resolve = false): array
    {
        $roles = $this->roles->all($namespace);

        if ($resolve) {
            $roles = arr($roles)
                ->map(fn(Role $role) => $this->resolve($role))
                ->toArray();
        }

        return $roles;
    }

    public function toRole(Role|string|int $role): ?Role
    {
        if (is_string($role)) {
            $role = Role::define($role);
        }

        if (is_int($role)) {
            $role = $this->find($role);
        }

        return $role;
    }

    private function resolve(Role $role): Role
    {
        $role->permissions = $this->role($role)->permissions()->all();
        $role->parents = $this->roleParents->getParents($role);
        $role->children = $this->roleParents->getChildren($role);
        return $role;
    }
}
