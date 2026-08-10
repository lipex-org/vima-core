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

namespace Vima\Core;

use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Entities\Role;
use Vima\Core\User\Services\UserService;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\User\Fluent\UserResource;
use Vima\Core\Role\Fluent\RoleResource;
use Vima\Core\Permission\Fluent\PermissionResource;
use Vima\Core\Support\Discovery\Container;

/**
 * Class VimaManager
 * 
 * An instance-based manager wrapper providing easy access to all services
 * and fluent APIs, suitable for exposure via helpers and container services.
 */
class VimaManager
{
    private Container $container;

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * Contextual fluent API for a specific user.
     */
    public function user(object $user): UserResource
    {
        return $this->container->get(UserService::class)->user($user);
    }

    /**
     * Contextual fluent API for a specific role.
     */
    public function role(string|int|Role $role): RoleResource
    {
        return $this->container->get(RoleService::class)->role($role);
    }

    /**
     * Contextual fluent API for a specific permission.
     */
    public function permission(string|int|Permission $permission): PermissionResource
    {
        return $this->container->get(PermissionService::class)->permission($permission);
    }

    /**
     * Global role service for operations like create, find, all.
     */
    public function roles(): RoleService
    {
        return $this->container->get(RoleService::class);
    }

    /**
     * Global permission service for operations like create, find, all.
     */
    public function permissions(): PermissionService
    {
        return $this->container->get(PermissionService::class);
    }

    /**
     * Get the policy registry.
     */
    public function policies(): PolicyRegistry
    {
        return $this->container->get(PolicyRegistry::class);
    }

    /**
     * The core authorization evaluation engine.
     */
    public function auth(): AuthorizationService
    {
        return $this->container->get(AuthorizationService::class);
    }

    /**
     * Evaluate permission for a user.
     */
    public function can(object $user, string $permission, ...$arguments): bool
    {
        return $this->auth()->can($user, $permission, ...$arguments);
    }

    /**
     * Enforce permission check, throwing an exception on failure.
     */
    public function enforce(object $user, string $permission, ...$arguments): void
    {
        $this->auth()->enforce($user, $permission, ...$arguments);
    }
}
