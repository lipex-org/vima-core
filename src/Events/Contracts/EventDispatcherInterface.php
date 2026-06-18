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

namespace Vima\Core\Events\Contracts;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param object $event The event object to dispatch.
     * @return object The dispatched event.
     */
    public function dispatch(object $event): object;
}