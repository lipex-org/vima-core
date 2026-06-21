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

namespace Vima\Core\Support\Discovery;

// Basic configurations and caching
use Vima\Core\Audit\Contracts\AuditRepositoryInterface;
use Vima\Core\Audit\Services\AuditService;
use Vima\Core\Config\Services\SyncService;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Cache\Contracts\CacheInterface;
use Vima\Core\Cache\Adapters\SymfonyCacheAdapter;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Dispatchers\DefaultEventDispatcher;

// Repositories
use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Permission\Contracts\PermissionRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;

// Services
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Services\UserService;
use Vima\Core\User\Services\UserResolutionService;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\AuthorizationService;

/**
 * Class CoreBootstrapper
 * 
 * Sets up the base dependency container bindings for the Vima core engine.
 */
class CoreBootstrapper
{
    public static function bootstrap(Container $container): void
    {
        // Bind singletons for interfaces
        $container->register(CacheInterface::class, fn() => new SymfonyCacheAdapter());
        $container->register(EventDispatcherInterface::class, fn() => new DefaultEventDispatcher());

        if (!$container->get(VimaConfig::class)) {
            $container->register(VimaConfig::class, fn() => new VimaConfig());
        }

        // We assume repos are bound externally by the consumer (e.g. CI4 or Laravel bridge).
        // For testing, we could bind arrays or mock repositories here.

        // Register core services
        $container->register(PolicyRegistry::class, fn($c) => new PolicyRegistry(
            $c->get(EventDispatcherInterface::class),
            $c->get(CacheInterface::class),
            $c->get(VimaConfig::class)
        ));

        $container->register(UserResolutionService::class, fn($c) => new UserResolutionService(
            $c->get(VimaConfig::class)
        ));

        $container->register(RoleService::class, fn($c) => new RoleService(
            $c->get(RoleRepositoryInterface::class),
            $c->get(RolePermissionRepositoryInterface::class),
            $c->get(RoleParentRepositoryInterface::class),
            $c->get(EventDispatcherInterface::class)
        ));

        $container->register(PermissionService::class, fn($c) => new PermissionService(
            $c->get(PermissionRepositoryInterface::class),
            $c->get(EventDispatcherInterface::class)
        ));

        $container->register(UserService::class, fn($c) => new UserService(
            $c->get(UserResolutionService::class),
            $c->get(RoleService::class),
            $c->get(PermissionService::class),
            $c->get(UserRoleRepositoryInterface::class),
            $c->get(UserPermissionRepositoryInterface::class),
            $c->get(UserDenyRepositoryInterface::class),
            $c->get(UserRoleDenyRepositoryInterface::class),
            $c->get(EventDispatcherInterface::class),
            $c->get(VimaConfig::class),
            $c->get(CacheInterface::class)
        ));

        $container->register(AuthorizationService::class, fn($c) => new AuthorizationService(
            $c->get(UserService::class),
            $c->get(RoleService::class),
            $c->get(PolicyRegistry::class),
            $c->get(VimaConfig::class),
            $c->get(EventDispatcherInterface::class)
        ));

        $container->register(AuditService::class, fn($c) => new AuditService(
            $c->get(AuditRepositoryInterface::class),
            $c->get(VimaConfig::class),
            $c->get(UserResolutionService::class)
        ));

        $container->register(SyncService::class, fn($c) => new SyncService(
            $c->get(RoleService::class),
            $c->get(PermissionService::class),
            $c->get(EventDispatcherInterface::class),
            $c->get(VimaConfig::class)
        ));
    }
}
