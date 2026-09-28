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

use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Entities\RoleParent;
use Vima\Core\Role\Contracts\RoleParentRepositoryInterface;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Cache\Services\CacheVersionManager;
use Vima\Core\Events\DomainEvent;
use function Vima\Core\resolve;

class RoleParentsBuilder
{
    private ?CacheVersionManager $versionManager = null;

    public function __construct(
        private Role $role,
        private RoleParentRepositoryInterface $roleParents,
        private EventDispatcherInterface $dispatcher,
        ?CacheVersionManager $versionManager = null
    ) {
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
    }

    public function add(string|int $parentId): self
    {
        $this->roleParents->assign(new RoleParent(
            roleId: $this->role->id,
            parentId: $parentId
        ));

        if ($this->role->id !== null && $this->versionManager !== null) {
            $this->versionManager->bumpRoleEpoch($this->role->id);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.role.parent_added', [
            'role' => $this->role,
            'roleId' => $this->role->id,
            'parentId' => $parentId
        ]));

        return $this;
    }

    public function remove(string|int $parentId): self
    {
        $this->roleParents->remove(new RoleParent(
            roleId: $this->role->id,
            parentId: $parentId
        ));

        if ($this->role->id !== null && $this->versionManager !== null) {
            $this->versionManager->bumpRoleEpoch($this->role->id);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.role.parent_removed', [
            'role' => $this->role,
            'roleId' => $this->role->id,
            'parentId' => $parentId
        ]));

        return $this;
    }

    public function clear(): self
    {
        $this->roleParents->clearParents($this->role);

        if ($this->role->id !== null && $this->versionManager !== null) {
            $this->versionManager->bumpRoleEpoch($this->role->id);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.role.parents_cleared', [
            'role' => $this->role,
            'roleId' => $this->role->id
        ]));

        return $this;
    }

    /**
     * @return RoleParent[]
     */
    public function all(): array
    {
        return $this->roleParents->getParents($this->role);
    }
}
