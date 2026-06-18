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

namespace Vima\Core\Role\Contracts;

use Vima\Core\Role\Entities\Role;

/**
 * Interface RoleRepositoryInterface
 * 
 * Defines the contract for managing Role entities in persistent storage.
 */
interface RoleRepositoryInterface
{
    public function findByName(string $name): ?Role;

    public function findById(int|string $id): ?Role;

    /**
     * @param string|null $namespace
     * @return Role[]
     */
    public function all(?string $namespace = null): array;

    public function save(Role $role): Role;

    public function delete(Role $role): void;

    public function deleteAll(): void;
}
