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

namespace Vima\Core\User\Fluent;

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

class UserRevoke
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserRoleRepositoryInterface $userRoles,
        private UserPermissionRepositoryInterface $userPermissions,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function role(string|Role $role): void
    {
        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            return;
        }

        $this->userRoles->revoke(new \Vima\Core\User\Entities\UserRole(
            userId: $this->userId,
            roleId: $roleEntity->id
        ));
    }

    public function permission(string|Permission $permission): void
    {
        $permissionEntity = $this->permissionService->find($permission);
        if (!$permissionEntity) {
            return;
        }

        $this->userPermissions->remove(new \Vima\Core\User\Entities\UserPermission(
            userId: $this->userId,
            permissionId: $permissionEntity->id
        ));
    }
}
