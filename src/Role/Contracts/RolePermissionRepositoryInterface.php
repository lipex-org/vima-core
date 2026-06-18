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

namespace Vima\Core\Role\Contracts;

use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Entities\RolePermission;

interface RolePermissionRepositoryInterface
{
    /**
     * @param Role $role
     * @return RolePermission[]
     */
    public function getRolePermissions(Role $role): array;

    /**
     * @param Permission $permission
     * @return RolePermission[]
     */
    public function getPermissionRoles(Permission $permission): array;

    /**
     * @return RolePermission[]
     */
    public function all(): array;

    public function assign(RolePermission $permission): void;

    public function revoke(RolePermission $permission): void;

    public function deleteAll(): void;
}
