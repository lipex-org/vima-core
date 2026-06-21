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

namespace Vima\Core\Config\Schema;

/**
 * Class Tables
 * 
 * Table names for persistent storage.
 */
final class Tables
{
    public function __construct(
        public string $prefix = 'vima_',
        public ?string $roles = null,
        public ?string $permissions = null,
        public ?string $rolePermissions = null,
        public ?string $userRoles = null,
        public ?string $userPermissions = null,
        public ?string $roleParents = null,
        public ?string $userDenies = null,
        public ?string $userRoleDenies = null,
        public ?string $auditLogs = null,
    ) {
        $this->roles = $roles ?? $this->prefix . 'roles';
        $this->permissions = $permissions ?? $this->prefix . 'permissions';
        $this->rolePermissions = $rolePermissions ?? $this->prefix . 'role_permissions';
        $this->userRoles = $userRoles ?? $this->prefix . 'user_roles';
        $this->userPermissions = $userPermissions ?? $this->prefix . 'user_permissions';
        $this->roleParents = $roleParents ?? $this->prefix . 'role_parents';
        $this->userDenies = $userDenies ?? $this->prefix . 'user_denies';
        $this->userRoleDenies = $userRoleDenies ?? $this->prefix . 'user_role_denies';
        $this->auditLogs = $auditLogs ?? $this->prefix . 'audit_logs';
    }
}
