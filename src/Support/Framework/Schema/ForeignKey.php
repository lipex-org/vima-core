<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org/vima-core>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vima\Core\Support\Framework\Schema;

class ForeignKey
{
    public function __construct(
        public string $column,
        public string $onTable,
        public string $onColumn,
        public string $onDelete = 'CASCADE',
        public string $onUpdate = 'CASCADE'
    ) {
    }
}
