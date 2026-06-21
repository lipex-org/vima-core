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

namespace Vima\Core\Events\Dispatchers;

use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Events\Event;

/**
 * Class DefaultEventDispatcher
 * 
 * A simple synchronous event dispatcher implementation.
 */
class DefaultEventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    /**
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        $name = $event instanceof Event ? $event->getName() : get_class($event);

        if (isset($this->listeners[$name])) {
            foreach ($this->listeners[$name] as $listener) {
                $listener($event);
            }
        }

        return $event;
    }

    /**
     * Register a listener for an event.
     *
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function listen(string $eventName, callable $listener, int $priority = 100): void
    {
        $this->listeners[$eventName][] = $listener;
    }
}
