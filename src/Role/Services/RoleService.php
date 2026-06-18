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

namespace Vima\Core\Role\Services;

use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Fluent\RoleResource;
use Vima\Core\Support\Utils\Utils;

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
        if (!$roleEntity) {
            throw new \RuntimeException("Role not found.");
        }

        return new RoleResource(
            $roleEntity,
            $this,
            $this->rolePermissions,
            $this->roleParents,
            $this->dispatcher
        );
    }

    public function find(int|string|Role $role): ?Role
    {
        $id = null;
        $name = null;
        $namespace = null;

        if (is_int($role)) {
            $id = $role;
        } elseif (is_string($role)) {
            [$namespace, $name] = Utils::resolveNamespace($role);
        } else {
            $id = $role->id;
            $name = $role->name;
            $namespace = $role->namespace;
        }

        if ($id) {
            return $this->roles->findById($id);
        }

        // We assume the repository findByName takes the fully qualified name or 
        // we format it here if the repository still splits it. 
        // Let's pass the full name namespace:name to findByName so the repo can parse it, 
        // or we pass the resolved name and namespace if the repo interface allowed it. 
        // Since we removed $namespace from findByName, we must pass the full string.
        $fullName = $namespace ? "{$namespace}:{$name}" : $name;
        return $this->roles->findByName($fullName);
    }

    public function save(Role $role): Role
    {
        return $this->roles->save($role);
    }

    public function delete(Role $role): void
    {
        $this->roles->delete($role);
    }

    /**
     * @param string|null $namespace
     * @return Role[]
     */
    public function all(?string $namespace = null): array
    {
        return $this->roles->all($namespace);
    }

    /**
     * Gets all permissions assigned to a role and its parents.
     * 
     * @param string|Role $role
     * @param array $visited
     * @return Permission[]
     */
    public function getRolePermissions(string|Role $role, array &$visited = []): array
    {
        $roleEntity = $this->find($role);
        if (!$roleEntity) {
            return [];
        }

        $roleKey = $roleEntity->getFullName();
        if (in_array($roleKey, $visited)) {
            return [];
        }
        $visited[] = $roleKey;

        $perms = [];
        $permissionService = \Vima\Core\Support\Discovery\Container::getInstance()->get(\Vima\Core\Permission\Services\PermissionService::class);

        $rolePerms = $this->rolePermissions->getRolePermissions($roleEntity);
        foreach ($rolePerms as $rp) {
            $perm = $permissionService->find($rp->permissionId);
            if ($perm) {
                $p = clone $perm;
                $p->constraints = $rp->constraints ?? [];
                $perms[] = $p;
            }
        }

        $parents = $this->roleParents->getParents($roleEntity);
        foreach ($parents as $parent) {
            $perms = array_merge($perms, $this->getRolePermissions((string)$parent->parentId, $visited));
        }

        return $perms;
    }
}
