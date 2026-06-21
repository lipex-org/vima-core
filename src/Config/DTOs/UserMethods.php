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

namespace Vima\Core\Config\DTOs;

/**
 * Class UserMethods
 * 
 * Mapping for user object methods.
 */
final class UserMethods
{
    public function __construct(
        public ?string $id = null,
    ) {
    }
}
