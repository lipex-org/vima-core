<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Vima\Core\Events\Dispatchers\DefaultEventDispatcher;
use Vima\Core\Events\Event;

class DummyEvent extends Event
{
    public const NAME = 'dummy.event';
}

class DefaultEventDispatcherTest extends TestCase
{
    public function testDispatcherRegistersAndTriggersListeners()
    {
        $dispatcher = new DefaultEventDispatcher();
        
        $triggered = false;
        $receivedEvent = null;

        $dispatcher->listen('dummy.event', function (object $event) use (&$triggered, &$receivedEvent) {
            $triggered = true;
            $receivedEvent = $event;
        });

        $event = new DummyEvent(['foo' => 'bar']);
        $dispatcher->dispatch($event);

        $this->assertTrue($triggered);
        $this->assertSame($event, $receivedEvent);
        $this->assertEquals('bar', $receivedEvent->get('foo'));
    }

    public function testDispatcherUsesClassNameIfNoNameConstantProvided()
    {
        $dispatcher = new DefaultEventDispatcher();
        
        $event = new class extends Event {};
        $eventName = get_class($event);

        $triggered = false;
        $dispatcher->listen($eventName, function () use (&$triggered) {
            $triggered = true;
        });

        $dispatcher->dispatch($event);

        $this->assertTrue($triggered);
    }
}
