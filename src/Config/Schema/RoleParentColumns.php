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

namespace Vima\Core\Config\Schema;

final class RoleParentColumns
{
    public function __construct(
        public string $id = 'id',
        public string $roleId = 'role_id',
        public string $parentId = 'parent_id',
    ) {
    }
}
