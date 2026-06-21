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

namespace Vima\Core\User\Fluent;

use Vima\Core\Events\DomainEvent;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\User\Entities\UserPermission;
use Vima\Core\User\Entities\UserRole;

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

        $this->userRoles->revoke(new UserRole(
            userId: $this->userId,
            roleId: $roleEntity->id
        ));
        $this->dispatcher->dispatch(new DomainEvent('vima.user.role_revoked', [
            'userId' => $this->userId,
            'role' => $roleEntity
        ]));
    }

    public function permission(string|Permission $permission): void
    {
        $permissionEntity = $this->permissionService->find($permission);
        if (!$permissionEntity) {
            return;
        }

        $this->userPermissions->remove(new UserPermission(
            userId: $this->userId,
            permissionId: $permissionEntity->id
        ));
        $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_revoked', [
            'userId' => $this->userId,
            'permission' => $permissionEntity
        ]));
    }
}
