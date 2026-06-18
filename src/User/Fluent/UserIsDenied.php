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
use Vima\Core\Support\Utils\Utils;

class UserIsDenied
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies
    ) {
    }

    public function role(string|Role $role): bool
    {
        $roleRecord = $this->roleService->find($role);
        if (!$roleRecord) return false;

        $denies = $this->userRoleDenies->getDeniedRoles($this->userId);
        foreach ($denies as $deny) {
            if ($deny->expiresAt && strtotime($deny->expiresAt) < time()) {
                continue;
            }
            if ($deny->roleId === $roleRecord->id) {
                return true;
            }
        }
        return false;
    }

    public function permission(string|Permission $permission): bool
    {
        $permName = '';
        $permNamespace = null;

        if (is_string($permission)) {
            [$permNamespace, $permName] = Utils::resolveNamespace($permission);
        } elseif ($permission instanceof Permission) {
            $permName = $permission->name;
            $permNamespace = $permission->namespace;
        }

        $denies = $this->userDenies->getDeniedPermissions($this->userId);

        foreach ($denies as $deny) {
            if ($deny->expiresAt && strtotime($deny->expiresAt) < time()) {
                continue;
            }

            $denyId = $deny->permissionId;

            if ($denyId === '*') {
                return true;
            }
            if ($permNamespace && $denyId === $permNamespace . ':*') {
                return true;
            }

            $deniedPerm = $this->permissionService->find($denyId);
            if (!$deniedPerm) continue;

            if ($deniedPerm->name === $permName && $deniedPerm->namespace === $permNamespace) {
                return true;
            }
            if ($deniedPerm->namespace === $permNamespace && $deniedPerm->name === '*') {
                return true;
            }
        }

        return false;
    }
}
