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

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use DateTimeInterface;

use Vima\Core\Events\DomainEvent;

/**
 * Class UserDeny
 * 
 * Fluent API for explicitly denying roles and permissions to a user.
 */
class UserDeny
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function role(string|Role $role, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            return;
        }

        $this->userRoleDenies->add(
            $this->userId,
            $roleEntity->id,
            $reason,
            $expiresAt
        );

        $this->dispatcher->dispatch(new DomainEvent('vima.user.role_denied', [
            'userId' => $this->userId,
            'role' => $roleEntity,
            'reason' => $reason,
            'expiresAt' => $expiresAt
        ]));
    }

    public function permission(string|Permission $permission, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        $permissionEntity = $this->permissionService->find($permission);
        if (!$permissionEntity) {
            return;
        }

        $this->userDenies->add(
            $this->userId,
            $permissionEntity->id,
            $reason,
            $expiresAt
        );

        $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_denied', [
            'userId' => $this->userId,
            'permission' => $permissionEntity,
            'reason' => $reason,
            'expiresAt' => $expiresAt
        ]));
    }
}
