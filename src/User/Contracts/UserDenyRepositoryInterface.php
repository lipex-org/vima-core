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

use Vima\Core\User\Entities\UserDeny;
use DateTimeInterface;

/**
 * Interface UserDenyRepositoryInterface
 * 
 * Handles persistence of explicit permission denials for users.
 */
interface UserDenyRepositoryInterface
{
    public function add(string|int $userId, string|int $permissionId, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void;

    public function remove(string|int $userId, string|int $permissionId): void;

    public function isDenied(string|int $userId, string|int $permissionId): bool;

    /**
     * @param string|int $userId
     * @return UserDeny[]
     */
    public function getDeniedPermissions(string|int $userId): array;
}
