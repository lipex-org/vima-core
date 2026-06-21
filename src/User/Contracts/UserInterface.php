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

/**
 * Interface UserInterface
 * 
 * Contract for user objects that can be identified within the Vima system.
 */
interface UserInterface
{
    /**
     * Get the unique identifier for the user.
     *
     * @return string|int
     */
    public function vimaGetId(): string|int;
}
