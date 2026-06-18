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

namespace Vima\Core\Role\Fluent;

use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Entities\RolePermission;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

class RolePermissionsBuilder
{
    public function __construct(
        private Role $role,
        private RolePermissionRepositoryInterface $rolePermissions,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function add(string|int $permissionId, array $constraints = []): self
    {
        $this->rolePermissions->assign(new RolePermission(
            roleId: $this->role->id,
            permissionId: $permissionId,
            constraints: $constraints
        ));
        return $this;
    }

    public function remove(string|int $permissionId): self
    {
        $this->rolePermissions->revoke(new RolePermission(
            roleId: $this->role->id,
            permissionId: $permissionId
        ));
        return $this;
    }

    /**
     * @return RolePermission[]
     */
    public function all(): array
    {
        return $this->rolePermissions->getRolePermissions($this->role);
    }
}
