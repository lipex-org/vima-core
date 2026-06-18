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

namespace Vima\Core\Permission\Contracts;

use Vima\Core\Permission\Entities\Permission;

/**
 * Interface PermissionRepositoryInterface
 * 
 * Defines the contract for managing Permission entities in persistent storage.
 */
interface PermissionRepositoryInterface
{
    public function findByName(string $name): ?Permission;

    public function findById(int|string $id): ?Permission;

    /**
     * @param string|null $namespace
     * @return Permission[]
     */
    public function all(?string $namespace = null): array;

    public function save(Permission $permission): Permission;

    public function delete(Permission $permission): void;

    public function deleteAll(): void;
}
