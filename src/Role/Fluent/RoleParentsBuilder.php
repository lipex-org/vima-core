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

class RoleParentsBuilder
{
    public function __construct(
        private Role $role,
        private RoleParentRepositoryInterface $roleParents,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function add(string|int $parentId): self
    {
        $this->roleParents->assign(new RoleParent(
            roleId: $this->role->id,
            parentId: $parentId
        ));
        return $this;
    }

    public function remove(string|int $parentId): self
    {
        $this->roleParents->remove(new RoleParent(
            roleId: $this->role->id,
            parentId: $parentId
        ));
        return $this;
    }

    public function clear(): self
    {
        $this->roleParents->clearParents($this->role);
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
