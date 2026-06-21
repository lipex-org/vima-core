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

namespace Vima\Core\User\Contracts;

use Vima\Core\User\Entities\UserRole;

interface UserRoleRepositoryInterface
{
    /**
     * @param int|string $userId
     * @return UserRole[]
     */
    public function getRolesForUser(int|string $userId): array;

    public function assign(UserRole $userRole): void;

    public function revoke(UserRole $userRole): void;
}
