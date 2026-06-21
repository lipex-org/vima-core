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

namespace Vima\Core\User\Fluent;

use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;

class UserGetDenies
{
    public function __construct(
        private int|string $userId,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies
    ) {
    }

    public function permission(): array
    {
        return $this->userDenies->getDeniedPermissions($this->userId);
    }

    public function role(): array
    {
        return $this->userRoleDenies->getDeniedRoles($this->userId);
    }
}
