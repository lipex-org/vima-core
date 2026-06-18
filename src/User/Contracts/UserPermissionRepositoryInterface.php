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

namespace Vima\Core\User\Contracts;

use Vima\Core\User\Entities\UserPermission;

interface UserPermissionRepositoryInterface
{
    /**
     * @param int|string $userId
     * @return UserPermission[]
     */
    public function findByUserId(int|string $userId): array;

    public function add(UserPermission $permission): void;

    public function remove(UserPermission $permission): void;
}
