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
use Vima\Core\Config\VimaConfig;

class UserIs
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies,
        private VimaConfig $config,
        private UserHas $userHas
    ) {
    }

    public function denied(): UserIsDenied
    {
        return new UserIsDenied(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userDenies,
            $this->userRoleDenies
        );
    }

    public function superAdmin(): bool
    {
        $superAdminRole = $this->config->superAdminRole;
        if (!$superAdminRole) {
            return false;
        }

        return $this->userHas->role($superAdminRole);
    }
}
