# Vima Core API Reference

The Vima Core library uses a dual-facade design pattern on the `Vima` class. This segregates contextual (singular) operations from global/bulk (plural) operations.

---

## 1. Global / Plural Service APIs

Plural methods return stateless domain services used for entity query and creation.

### `Vima::roles(): RoleService`
Accesses the `RoleService` instance.
- **Get all roles**: `Vima::roles()->all(?string $namespace = null, bool $resolve = false): array`
- **Find a role**: `Vima::roles()->find(int|string|Role $role, bool $resolve = false): ?Role`
- **Save a role**: `Vima::roles()->save(Role $role): Role`

### `Vima::permissions(): PermissionService`
Accesses the `PermissionService` instance.
- **Get all permissions**: `Vima::permissions()->all(?string $namespace = null): array`
- **Create a permission**: `Vima::permissions()->create(string|Permission $name, ?string $description = null): Permission`
- **Find a permission**: `Vima::permissions()->find(int|string|Permission $permission): ?Permission`

---

## 2. Contextual / Singular Fluent APIs

Singular methods return fluent resource builders to easily perform actions or verify relationships.

### `Vima::role(string|int|Role $role): RoleResource`
Returns a `RoleResource` instance.
- **`exists(): bool`**: Checks if the role exists in the repository.
- **`ensure(): RoleResource`**: Saves the role if it does not exist, then returns the resource wrapper.
- **`original(): Role`**: Retrieves the raw database `Role` entity.
- **`delete(): void`**: Deletes the role.
- **`permissions(): RolePermissionsBuilder`**:
  - **`add(string|Permission|array $permission, array $constraints = []): self`**: Grants a permission or an array of permissions (bulk) to this role.
  - **`remove(string|Permission|array $permission): self`**: Revokes a permission or an array of permissions (bulk) from this role.
  - **`all(): array`**: Returns all permissions associated with this role, including inherited parent permissions.
- **`parents(): RoleParentsBuilder`**:
  - **`add(string|int $parentId): self`**: Sets a parent role to inherit permissions from.
  - **`remove(string|int $parentId): self`**: Removes a parent role association.

### `Vima::permission(string|int|Permission $permission): PermissionResource`
Returns a `PermissionResource` instance.
- **`exists(): bool`**: Checks if the permission exists.
- **`ensure(): PermissionResource`**: Ensures existence or throws a `RuntimeException`.
- **`delete(): void`**: Deletes the permission from the database.

### `Vima::user(object $user): UserResource`
Returns a `UserResource` wrapper for user-specific assignment checks:
- **`grant(): UserGrant`**: Grant roles or permissions to this user.
  - **`role(string|Role|array $role, array $context = []): void`**: Grants a role or an array of roles (bulk) to the user.
  - **`permission(string|Permission|array $permission, array $constraints = []): void`**: Grants a direct permission or an array of direct permissions (bulk) to the user.
- **`revoke(): UserRevoke`**: Revoke roles or permissions from this user.
  - **`role(string|Role|array $role): void`**: Revokes a role or an array of roles (bulk) from the user.
  - **`permission(string|Permission|array $permission): void`**: Revokes a direct permission or an array of direct permissions (bulk) from the user.
- **`deny(): UserDeny`**: Explicitly deny roles or permissions to this user.
  - **`role(string|Role|array $role, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void`**: Denies a role or an array of roles (bulk) to the user.
  - **`permission(string|Permission|array $permission, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void`**: Denies a permission or an array of permissions (bulk) to the user.
- **`undeny(): UserUndeny`**: Remove explicit denials of roles or permissions from this user.
  - **`role(string|Role|array $role): void`**: Removes a denial for a role or an array of roles (bulk).
  - **`permission(string|Permission|array $permission): void`**: Removes a denial for a permission or an array of permissions (bulk).
- **`roles(): UserRolesBuilder`**: Assign, revoke, or retrieve roles for this specific user.
- **`permissions(): UserPermissionsBuilder`**: Grant, revoke, or retrieve direct permissions for this specific user.

---

## 3. Policy Registry API (`Vima::policies()`)

Allows manual configuration of callbacks for Attribute-Based Access Control (ABAC):
- **Register callback**: `Vima::policies()->register(string $ability, callable $callback): void`
- **Register class**: `Vima::policies()->registerClass(string $resourceClass, string $policyClass): void`

