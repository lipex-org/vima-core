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

namespace Vima\Core\User\Services;

use Vima\Core\User\Fluent\UserResource;
use Vima\Core\User\Services\UserResolutionService;
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
 * Class UserService
 * 
 * Operational service for user-related access control.
 */
class UserService
{
    public function __construct(
        private UserResolutionService $userResolution,
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

    /**
     * @param object $user
     * @return UserResource
     */
    public function user(object $user): UserResource
    {
        return new UserResource(
            $user,
            $this->userResolution->resolveId($user),
            $this->roleService,
            $this->permissionService,
            $this->userRoles,
            $this->userPermissions,
            $this->userDenies,
            $this->userRoleDenies,
            $this->dispatcher,
            $this->config,
            $this->cache
        );
    }
}
