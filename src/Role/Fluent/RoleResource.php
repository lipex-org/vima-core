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

namespace Vima\Core\Role\Fluent;

use RuntimeException;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Contracts\RolePermissionRepositoryInterface;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

/**
 * Class RoleResource
 * 
 * Fluent API root for role-specific operations.
 */
class RoleResource
{
    private ?Role $resolvedRole = null;

    public function __construct(
        private Role|string|int $role,
        private RoleService $roleService,
        private RolePermissionRepositoryInterface $rolePermissions,
        private RoleParentRepositoryInterface $roleParents,
        private EventDispatcherInterface $dispatcher
    ) {
        if ($role instanceof Role) {
            $this->resolvedRole = $role;
        }
    }

    private function resolveRole(): ?Role
    {
        if ($this->resolvedRole === null) {
            $this->resolvedRole = $this->roleService->find($this->role);
        }
        return $this->resolvedRole;
    }

    public function exists(): bool
    {
        return $this->resolveRole() !== null;
    }

    public function ensure(): self
    {
        if (!$this->exists()) {
            $role = $this->roleService->toRole($this->role);

            if (!$role) {
                throw new RuntimeException("Role cannot be resolved as it does exist");
            }

            $this->resolvedRole = $this->roleService->save($role);
        }
        return $this;
    }

    public function permissions(): RolePermissionsBuilder
    {
        $role = $this->resolveRole();
        if (!$role) {
            throw new RuntimeException("Cannot get permissions builder for non-existent role.");
        }
        return new RolePermissionsBuilder($role, $this->rolePermissions, $this->dispatcher);
    }

    public function parents(): RoleParentsBuilder
    {
        $role = $this->resolveRole();
        if (!$role) {
            throw new RuntimeException("Cannot get parents builder for non-existent role.");
        }
        return new RoleParentsBuilder($role, $this->roleParents, $this->dispatcher);
    }

    public function original(): Role
    {
        $resolved = $this->resolveRole();
        if (!$resolved) {
            throw new RuntimeException("Cannot retrieve original role because it does not exist.");
        }
        return $resolved;
    }

    public function delete(): void
    {
        $resolved = $this->resolveRole();
        if ($resolved) {
            $this->roleService->delete($resolved);
            $this->resolvedRole = null;
        }
    }
}
