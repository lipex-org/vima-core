# Vima Event System

Vima contains an event dispatcher module. Every significant authentication decision, CRUD change, or user assignment fires synchronous events that can be listened to in order to generate audit logs, trigger cache purges, or integrate with custom workflows.

---

## 1. Available Core Events

All events extend `Vima\Core\Events\Event` and contain custom payloads.

### Authorization & Access Events
- **`vima.access.authorization_checked`** (`Vima\Core\Events\Access\AuthorizationChecked`):
  Fires on every authorization check query evaluated through `can()`.
  - **Payload**: `user`, `permission`, `namespace`, `arguments`, `result` (bool), `reason` (string|null).
- **`vima.access.denied`** (`Vima\Core\Events\Access\AccessDenied`):
  Fires when an access query evaluated via `enforce()` fails before throwing an exception.
  - **Payload**: `user`, `permission`, `namespace`, `arguments`.

### Schema & Sync Events
- **`vima.sync.started`** (`Vima\Core\Events\Sync\SyncStarted`):
  Fires when database declarative definition synchronization begins.
- **`vima.sync.finished`** (`Vima\Core\Events\Sync\SyncFinished`):
  Fires when database definitions sync successfully.
- **`vima.policy.registered`** (`Vima\Core\Policy\Events\PolicyRegistered`):
  Fires when a dynamic or class policy is registered.

### CRUD & Database Actions (`Vima\Core\Events\DomainEvent`)
These generic domain events are fired on service data modifications:
- **`vima.role.created`**: Fires when a new role is persisted.
- **`vima.role.updated`**: Fires when an existing role is updated.
- **`vima.role.deleted`**: Fires when a role is deleted.
- **`vima.permission.created`**: Fires when a new permission is registered.
- **`vima.permission.updated`**: Fires when an existing permission description is updated.
- **`vima.permission.deleted`**: Fires when a permission is removed.

### User Roles & Assignments (`Vima\Core\Events\DomainEvent`)
- **`vima.user.role_granted`**: Fires when a role is granted to a user.
- **`vima.user.permission_granted`**: Fires when a direct permission is assigned to a user.
- **`vima.user.role_revoked`**: Fires when a user role is revoked.
- **`vima.user.permission_revoked`**: Fires when a direct user permission is revoked.

---

## 2. Registering Event Listeners

If using the default Vima dispatcher, listen to events programmatically using:

```php
use Vima\Core\resolve;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Access\AuthorizationChecked;

/** @var EventDispatcherInterface $dispatcher */
$dispatcher = resolve(EventDispatcherInterface::class);

$dispatcher->listen(AuthorizationChecked::NAME, function (AuthorizationChecked $event) {
    $data = $event->getData();
    // E.g. Log: "User 1 checked posts.edit => Allowed"
    logger("User {$data['user']->id} checked {$data['permission']} => " . ($data['result'] ? 'Allowed' : 'Denied'));
});
```
