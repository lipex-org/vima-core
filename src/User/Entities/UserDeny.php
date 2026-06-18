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

namespace Vima\Core\User\Entities;

/**
 * Class UserDeny
 * 
 * Represents an explicit permission denial for a user.
 */
class UserDeny
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $userId = null,
        public int|string|null $permissionId = null,
        public ?string $namespace = null,
        public ?string $reason = null,
        public ?string $expiresAt = null,
        public ?string $createdAt = null
    ) {
    }
}
