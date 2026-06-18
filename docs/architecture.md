# Vima Core Architecture

Vima Core v1 is structured to promote maintainability, testability, and a fluid developer experience.

## Domain-Driven Design (DDD)
The source code is organized into vertical domains. Each domain directory (e.g., `src/Role/`) contains its own internal structure to isolate concerns:
- `Contracts/`: Interfaces required by the domain.
- `Entities/`: Pure POPOs (Plain Old PHP Objects) representing the domain models.
- `Services/`: Stateless operational logic handlers.
- `Fluent/`: Builder classes that implement the Fluent API.
- `Exceptions/`: Domain-specific exceptions.

## The Fluent API (Singular vs. Plural)
Vima Core uses a dual-facade pattern on the `Vima` class to distinguish between contextual (singular) operations and global (plural) operations:
- **`Vima::user($user)` / `Vima::role($role)`**: Return **Resource** objects for fluent chaining (e.g., `->grant()`, `->permissions()`).
- **`Vima::roles()` / `Vima::permissions()`**: Return **Services** for bulk actions like `->all()`, `->create()`, or `->deleteAll()`.

## Storage & Configuration
Vima Core is database-agnostic, relying on repository interfaces. The `Config/Schema` namespace allows framework integrators to generate migrations based on configured table names and automatically applied prefixes.
