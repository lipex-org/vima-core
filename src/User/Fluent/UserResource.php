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
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Cache\Contracts\CacheInterface;

/**
 * Class UserResource
 * 
 * Fluent API root for user-specific operations.
 */
class UserResource
{
    public function __construct(
        private object $user,
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserRoleRepositoryInterface $userRoles,
        private UserPermissionRepositoryInterface $userPermissions,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies,
        private EventDispatcherInterface $dispatcher,
        private VimaConfig $config,
        private ?CacheInterface $cache = null
    ) {
    }

    public function grant(): UserGrant
    {
        return new UserGrant(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userRoles,
            $this->userPermissions,
            $this->dispatcher
        );
    }

    public function revoke(): UserRevoke
    {
        return new UserRevoke(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userRoles,
            $this->userPermissions,
            $this->dispatcher
        );
    }

    public function deny(): UserDeny
    {
        return new UserDeny(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userDenies,
            $this->userRoleDenies,
            $this->dispatcher
        );
    }

    public function undeny(): UserUndeny
    {
        return new UserUndeny(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userDenies,
            $this->userRoleDenies,
            $this->dispatcher
        );
    }

    public function is(): UserIs
    {
        return new UserIs(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userDenies,
            $this->userRoleDenies,
            $this->config,
            $this->has()
        );
    }

    public function has(): UserHas
    {
        return new UserHas(
            $this->userId,
            $this->roleService,
            $this->get(),
            new UserIsDenied(
                $this->userId,
                $this->roleService,
                $this->permissionService,
                $this->userDenies,
                $this->userRoleDenies
            ),
            $this->config
        );
    }

    public function get(): UserGet
    {
        return new UserGet(
            $this->userId,
            $this->roleService,
            $this->permissionService,
            $this->userRoles,
            $this->userPermissions,
            $this->userDenies,
            $this->userRoleDenies,
            new UserIsDenied(
                $this->userId,
                $this->roleService,
                $this->permissionService,
                $this->userDenies,
                $this->userRoleDenies
            ),
            $this->config,
            $this->cache
        );
    }

    public function getId(): int|string
    {
        return $this->userId;
    }

    public function getOriginalUser(): object
    {
        return $this->user;
    }
}
