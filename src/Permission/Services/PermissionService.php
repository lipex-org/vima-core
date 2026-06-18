<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Permission\Services;

use Vima\Core\Permission\Contracts\PermissionRepositoryInterface;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Support\Utils\Utils;

/**
 * Class PermissionService
 * 
 * Operational service for managing permissions.
 */
class PermissionService
{
    public function __construct(
        private PermissionRepositoryInterface $permissions,
        private \Vima\Core\Events\Contracts\EventDispatcherInterface $dispatcher
    ) {
    }

    public function permission(int|string|Permission $permission): \Vima\Core\Permission\Fluent\PermissionResource
    {
        $permissionEntity = $this->find($permission);
        if (!$permissionEntity) {
            throw new \RuntimeException("Permission not found.");
        }

        return new \Vima\Core\Permission\Fluent\PermissionResource(
            $permissionEntity,
            $this,
            $this->dispatcher
        );
    }

    public function create(string|Permission $name, ?string $description = null): Permission
    {
        $permission = $name instanceof Permission ? $name : new Permission($name);

        if ($description !== null) {
            $permission->description = $description;
        }

        return $this->save($permission);
    }

    public function find(int|string|Permission $permission): ?Permission
    {
        $id = null;
        $name = null;
        $namespace = null;

        if (is_int($permission)) {
            $id = $permission;
        } elseif (is_string($permission)) {
            [$namespace, $name] = Utils::resolveNamespace($permission);
        } else {
            $id = $permission->id;
            $name = $permission->name;
            $namespace = $permission->namespace;
        }

        if ($id) {
            return $this->permissions->findById($id);
        }

        $fullName = $namespace ? "{$namespace}:{$name}" : $name;
        return $this->permissions->findByName($fullName);
    }

    public function save(Permission $permission): Permission
    {
        return $this->permissions->save($permission);
    }

    public function delete(Permission $permission): void
    {
        $this->permissions->delete($permission);
    }

    /**
     * @param string|null $namespace
     * @return Permission[]
     */
    public function all(?string $namespace = null): array
    {
        return $this->permissions->all($namespace);
    }
}
