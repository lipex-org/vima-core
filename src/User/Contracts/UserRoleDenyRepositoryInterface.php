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

use Vima\Core\User\Entities\UserRoleDeny;
use DateTimeInterface;

/**
 * Interface UserRoleDenyRepositoryInterface
 * 
 * Handles persistence of explicit role denials for users.
 */
interface UserRoleDenyRepositoryInterface
{
    public function add(string|int $userId, string|int $roleId, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void;

    public function remove(string|int $userId, string|int $roleId): void;

    public function isDenied(string|int $userId, string|int $roleId): bool;

    /**
     * @param string|int $userId
     * @return UserRoleDeny[]
     */
    public function getDeniedRoles(string|int $userId): array;
}
