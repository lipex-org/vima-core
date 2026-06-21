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

namespace Vima\Core\Permission\Fluent;

use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

/**
 * Class PermissionResource
 * 
 * Fluent API root for permission-specific operations.
 */
class PermissionResource
{
    private ?Permission $resolvedPermission = null;

    public function __construct(
        private Permission|string|int $permission,
        private PermissionService $permissionService,
    ) {
        if ($permission instanceof Permission) {
            $this->resolvedPermission = $permission;
        }
    }

    private function resolvePermission(): ?Permission
    {
        if ($this->resolvedPermission === null) {
            $this->resolvedPermission = $this->permissionService->find($this->permission);
        }
        return $this->resolvedPermission;
    }

    public function exists(): bool
    {
        return $this->resolvePermission() !== null;
    }

    public function ensure(): self
    {
        if (!$this->exists()) {
            throw new \RuntimeException("Permission '{$this->permission}' does not exist.");
        }
        return $this;
    }

    public function original(): Permission
    {
        $resolved = $this->resolvePermission();
        if (!$resolved) {
            throw new \RuntimeException("Cannot retrieve original permission because it does not exist.");
        }
        return $resolved;
    }

    public function delete(): void
    {
        $resolved = $this->resolvePermission();
        if ($resolved) {
            $this->permissionService->delete($resolved);
            $this->resolvedPermission = null;
        }
    }
}
