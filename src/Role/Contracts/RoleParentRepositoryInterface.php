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

namespace Vima\Core\Role\Contracts;

use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Entities\RoleParent;

/**
 * Interface RoleParentRepositoryInterface
 * 
 * Defines the contract for managing role inheritance relationships in persistent storage.
 */
interface RoleParentRepositoryInterface
{
    public function assign(RoleParent $relationship): void;

    public function remove(RoleParent $relationship): void;

    public function clearParents(Role $role): void;

    /**
     * @param Role $role
     * @return RoleParent[]
     */
    public function getParents(Role $role): array;

    /**
     * @param Role $role
     * @return RoleParent[]
     */
    public function getChildren(Role $role): array;

    public function deleteAll(): void;
}
