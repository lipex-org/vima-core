<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Support\Discovery;

use PHPUnit\Framework\TestCase;
use Vima\Core\Support\Discovery\Container;

interface DummyInterface {}
class DummyImplementation implements DummyInterface {}
class DummyService {
    public function __construct(public DummyInterface $dependency) {}
}

class ContainerTest extends TestCase
{
    public function testContainerResolvesBindings()
    {
        $container = new Container();
        $container->register(DummyInterface::class, DummyImplementation::class);

        $resolved = $container->get(DummyInterface::class);
        $this->assertInstanceOf(DummyImplementation::class, $resolved);
    }

    public function testContainerAutoWiresDependencies()
    {
        $container = new Container();
        $container->register(DummyInterface::class, DummyImplementation::class);
        
        // DummyService is not explicitly registered, container should auto-wire it
        $service = $container->get(DummyService::class);
        
        $this->assertInstanceOf(DummyService::class, $service);
        $this->assertInstanceOf(DummyImplementation::class, $service->dependency);
    }

    public function testContainerReturnsSingletons()
    {
        $container = new Container();
        $container->register(DummyInterface::class, DummyImplementation::class);

        $instance1 = $container->get(DummyInterface::class);
        $instance2 = $container->get(DummyInterface::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testContainerThrowsOnUnresolvableInterface()
    {
        $container = new Container();
        
        $this->expectException(\RuntimeException::class);
        $container->get(DummyInterface::class); // Not registered
    }
}
