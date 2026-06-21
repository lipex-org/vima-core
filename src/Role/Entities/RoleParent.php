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

namespace Vima\Core\Role\Entities;

/**
 * Class RoleParent
 * 
 * Represents role inheritance.
 */
class RoleParent
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $roleId = null,
        public int|string|null $parentId = null
    ) {
    }
}
