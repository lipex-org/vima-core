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

namespace Vima\Core\Config\Schema;

final class UserDenyColumns
{
    public function __construct(
        public string $id = 'id',
        public string $userId = 'user_id',
        public string $permissionId = 'permission_id',
        public string $namespace = 'namespace',
        public string $reason = 'reason',
        public string $expiresAt = 'expires_at',
        public string $createdAt = 'created_at',
    ) {
    }
}
