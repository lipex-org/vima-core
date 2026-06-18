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

namespace Vima\Core;

use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Support\Discovery\Container;
use Vima\Core\User\Services\UserService;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\User\Fluent\UserResource;
use Vima\Core\Role\Fluent\RoleResource;
use Vima\Core\Permission\Fluent\PermissionResource;

/**
 * Class Vima
 * 
 * The main facade and entry point for the Vima access control system.
 */
final class Vima
{
    /**
     * Contextual fluent API for a specific user.
     *
     * @param object $user
     * @return UserResource
     */
    public static function user(object $user): UserResource
    {
        return self::container()->get(UserService::class)->user($user);
    }

    /**
     * Contextual fluent API for a specific role.
     *
     * @param string|int|Role $role
     * @return RoleResource
     */
    public static function role(string|int|Role $role): RoleResource
    {
        return self::container()->get(RoleService::class)->role($role);
    }

    /**
     * Contextual fluent API for a specific permission.
     *
     * @param string|int|Permission $permission
     * @return PermissionResource
     */
    public static function permission(string|int|Permission $permission): PermissionResource
    {
        return self::container()->get(PermissionService::class)->permission($permission);
    }

    /**
     * Global role service for operations like create, find, all.
     *
     * @return RoleService
     */
    public static function roles(): RoleService
    {
        return self::container()->get(RoleService::class);
    }

    /**
     * Global permission service for operations like create, find, all.
     *
     * @return PermissionService
     */
    public static function permissions(): PermissionService
    {
        return self::container()->get(PermissionService::class);
    }

    /**
     * Get the policy registry.
     *
     * @return PolicyRegistry
     */
    public static function policies(): PolicyRegistry
    {
        return self::container()->get(PolicyRegistry::class);
    }

    /**
     * The core authorization evaluation engine.
     *
     * @return AuthorizationService
     */
    public static function auth(): AuthorizationService
    {
        return self::container()->get(AuthorizationService::class);
    }

    /**
     * Resolve the dependency container.
     *
     * @return Container
     */
    public static function container(): Container
    {
        return Container::getInstance();
    }
}
