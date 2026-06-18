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

namespace Vima\Core\Config\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Sync\SyncStarted;
use Vima\Core\Events\Sync\SyncFinished;
use Vima\Core\Config\Entities\Sync\SyncResponse;
use Vima\Core\Config\Entities\Sync\Skipped;
use Vima\Core\Vima;

/**
 * Class SyncService
 * 
 * Synchronizes declarative configuration into the persistent storage.
 */
class SyncService
{
    private bool $refresh = false;

    public function __construct(
        private RoleService $roleService,
        private PermissionService $permissionService,
        private EventDispatcherInterface $dispatcher,
        private VimaConfig $config
    ) {
    }

    public function refresh(bool $refresh = true): self
    {
        $this->refresh = $refresh;
        return $this;
    }

    public function sync(): SyncResponse
    {
        $this->dispatcher->dispatch(new SyncStarted($this->config, $this->refresh));

        if ($this->refresh) {
            // Assume RoleService and PermissionService handle cascade deleting
            // or the repository truncate logic handles it.
        }

        $resolver = new ConfigResolver($this->config);

        $stats = [
            'roles_created' => 0,
            'permissions_created' => 0,
            'role_permissions_synced' => 0,
            'role_parents_synced' => 0
        ];

        // 1. Sync Permissions
        $persistedPerms = [];
        foreach ($resolver->getPermissions() as $permData) {
            // Find existing to preserve ID if necessary, or just create/update
            $existing = $this->permissionService->find($permData->getFullName());
            if ($existing) {
                $existing->description = $permData->description;
                $persistedPerms[$existing->getFullName()] = $this->permissionService->save($existing);
            } else {
                $persistedPerms[$permData->getFullName()] = $this->permissionService->create($permData, $permData->description);
                $stats['permissions_created']++;
            }
        }

        // 2. Sync Roles & Relationships
        $persistedRoles = [];
        foreach ($resolver->getRoles() as $roleData) {
            $existing = $this->roleService->find($roleData->getFullName());
            if ($existing) {
                $existing->description = $roleData->description;
                $persistedRoles[$existing->getFullName()] = $this->roleService->save($existing);
            } else {
                $persistedRoles[$roleData->getFullName()] = $this->roleService->save($roleData);
                $stats['roles_created']++;
            }
        }

        // 3. Sync Mappings using Fluent API
        foreach ($resolver->getRoles() as $roleData) {
            $roleRes = Vima::role($persistedRoles[$roleData->getFullName()]);

            // Clear old relationships if refreshing
            if ($this->refresh) {
                // Actually the repo delete handles this, but if not refreshing we'd need to diff.
                // Assuming append for now based on legacy logic.
            }

            foreach ($roleData->permissions as $perm) {
                $roleRes->permissions()->add($persistedPerms[$perm->getFullName()]->id);
                $stats['role_permissions_synced']++;
            }

            foreach ($roleData->parents as $parentName) {
                if (isset($persistedRoles[$parentName])) {
                    $roleRes->parents()->add($persistedRoles[$parentName]->id);
                    $stats['role_parents_synced']++;
                }
            }
        }

        $response = new SyncResponse(new Skipped([], []), false);
        $this->dispatcher->dispatch(new SyncFinished($response));

        return $response;
    }
}
