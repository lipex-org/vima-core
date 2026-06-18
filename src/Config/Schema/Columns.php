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

namespace Vima\Core\Config\Schema;

/**
 * Class Columns
 * 
 * Aggregates all column mapping classes.
 */
final class Columns
{
    public function __construct(
        public RoleColumns $roles = new RoleColumns(),
        public PermissionColumns $permissions = new PermissionColumns(),
        public RolePermissionColumns $rolePermissions = new RolePermissionColumns(),
        public UserRoleColumns $userRoles = new UserRoleColumns(),
        public UserPermissionColumns $userPermissions = new UserPermissionColumns(),
        public RoleParentColumns $roleParents = new RoleParentColumns(),
        public UserDenyColumns $userDenies = new UserDenyColumns(),
        public UserRoleDenyColumns $userRoleDenies = new UserRoleDenyColumns(),
        public AuditLogColumns $auditLogs = new AuditLogColumns(),
    ) {
    }
}
