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

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

/**
 * Class RoleResource
 * 
 * Fluent API root for role-specific operations.
 */
class RoleResource
{
    public function __construct(
        private Role $role,
        private RoleService $roleService,
        private RolePermissionRepositoryInterface $rolePermissions,
        private RoleParentRepositoryInterface $roleParents,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function permissions(): RolePermissionsBuilder
    {
        return new RolePermissionsBuilder($this->role, $this->rolePermissions, $this->dispatcher);
    }

    public function parents(): RoleParentsBuilder
    {
        return new RoleParentsBuilder($this->role, $this->roleParents, $this->dispatcher);
    }

    public function getOriginalRole(): Role
    {
        return $this->role;
    }
}
