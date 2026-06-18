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
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Support\Utils\Utils;

class UserUndeny
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

    public function role(string|Role $role): void
    {
        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            return;
        }

        $this->userRoleDenies->remove($this->userId, $roleEntity->id);
    }

    public function permission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            [$namespace, $name] = Utils::resolveNamespace($permission);
            if ($name === '*') {
                $pid = ($namespace ? $namespace . ':' : '') . '*';
                $this->userDenies->remove($this->userId, $pid);
                return;
            }
        }

        $permissionEntity = $this->permissionService->find($permission);
        if ($permissionEntity) {
            $this->userDenies->remove($this->userId, $permissionEntity->id);
        }
    }
}
