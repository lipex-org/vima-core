# Vima Core Integration Guide

Vima Core is database-agnostic and framework-agnostic. It can be integrated into any custom framework or application ecosystem (such as Symfony, Laravel, Laminas, or custom legacy codebases) by implementing repository contracts and loading the container dependency resolver.

---

## 1. Storage Integration (Repositories)

You must implement repository interfaces under `Vima\Core\Role\Contracts` and `Vima\Core\Permission\Contracts` matching your local database layer:

### Interfaces to Implement
- **`RoleRepositoryInterface`**: Fetch and save role entities.
- **`PermissionRepositoryInterface`**: Fetch and save permission entities.
- **`RolePermissionRepositoryInterface`**: Map role-to-permission pivots with constraints.
- **`RoleParentRepositoryInterface`**: Manage role inheritance chains.
- **`UserRoleRepositoryInterface`**: Map users to roles.
- **`UserPermissionRepositoryInterface`**: Map direct user permissions.
- **`UserDenyRepositoryInterface`**: Manage direct user permission blacklists.
- **`UserRoleDenyRepositoryInterface`**: Manage user role exclusions.
- **`AuditRepositoryInterface`**: Persist audit logs of access evaluations.

---

## 2. Framework Bootstrapping

Bind your concrete storage repository implementations into the Vima Core Container:

```php
use Vima\Core\Support\Discovery\Container;
use Vima\Core\Support\Discovery\CoreBootstrapper;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\DTOs\PolicyConfig;
use Vima\Core\Role\Contracts\RoleRepositoryInterface;
use App\Repositories\Vima\MyFrameworkRoleRepository;

// 1. Fetch singleton container instance
$container = Container::getInstance();

// 2. Register your database/repository drivers
$container->register(RoleRepositoryInterface::class, fn(Container $c) => new MyFrameworkRoleRepository());
// Register other repository contracts...

// 3. Bind configuration DTO
$container->register(VimaConfig::class, fn() => new VimaConfig(
    superAdminRole: 'super-admin',
    superAdminBypass: true,
    policy: new PolicyConfig(
        registered: [
            \App\Policies\BlogPolicy::class,
        ]
    )
));

// 4. Load core dependency definitions
CoreBootstrapper::bootstrap($container);
```

---

## 3. Adapting Events and Cache

Vima Core allows you to customize the Event Dispatcher and Caching mechanism to hook into your framework's pipelines.

### Custom Event Dispatcher
Implement `Vima\Core\Events\Contracts\EventDispatcherInterface` to forward audit logs and registration events to your framework's listener ecosystem:

```php
use Vima\Core\Events\Contracts\EventDispatcherInterface;

class MyEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): void
    {
        // Forward $event into your local framework event dispatch pipeline
    }
}

// Bind in the container
$container->register(EventDispatcherInterface::class, fn() => new MyEventDispatcher());
```

### Custom Cache Adapter
Implement `Vima\Core\Cache\Contracts\CacheInterface` to optimize authorization queries by caching permission lookup trees:

```php
use Vima\Core\Cache\Contracts\CacheInterface;

class MyCacheAdapter implements CacheInterface
{
    public function get(string $key): mixed { /* ... */ }
    public function set(string $key, mixed $value, ?int $ttl = null): bool { /* ... */ }
    public function clear(): bool { /* ... */ }
}

// Bind in the container
$container->register(CacheInterface::class, fn() => new MyCacheAdapter());
```
