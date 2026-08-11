<?php

namespace Vima\Core\Tests\Unit\Exceptions;

use Vima\Core\Tests\TestCase;
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\AccessDeniedExceptionInterface;

class AccessDeniedExceptionTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the factory to default
        \Vima\Core\Support\Discovery\Container::getInstance()->register(
            \Vima\Core\Exceptions\AccessDeniedExceptionFactoryInterface::class,
            fn() => new \Vima\Core\Exceptions\DefaultAccessDeniedExceptionFactory()
        );
        parent::tearDown();
    }

    public function testDefaultInstantiation()
    {
        $exception = AccessDeniedException::forPermission('some.permission');

        $this->assertInstanceOf(AccessDeniedExceptionInterface::class, $exception);
        $this->assertEquals('some.permission', $exception->getPermission());
    }

    public function testFactoryCallbackOverride()
    {
        $customException = new class('custom.permission') extends \RuntimeException implements AccessDeniedExceptionInterface {
            public function __construct(private string $permission)
            {
                parent::__construct('Custom access denied message');
            }

            public function getPermission(): string
            {
                return $this->permission;
            }

            public function getUser(): mixed
            {
                return null;
            }

            public function getUserId(): ?string
            {
                return null;
            }
        };

        $factoryMock = new class($customException) implements \Vima\Core\Exceptions\AccessDeniedExceptionFactoryInterface {
            public function __construct(private \Throwable&AccessDeniedExceptionInterface $exception) {}
            public function create(string $permission, mixed $user = null, mixed $userResolver = null): \Throwable&AccessDeniedExceptionInterface
            {
                return $this->exception;
            }
        };

        \Vima\Core\Support\Discovery\Container::getInstance()->register(
            \Vima\Core\Exceptions\AccessDeniedExceptionFactoryInterface::class,
            fn() => $factoryMock
        );

        $exception = AccessDeniedException::forPermission('custom.permission');

        $this->assertInstanceOf(AccessDeniedExceptionInterface::class, $exception);
        $this->assertEquals('custom.permission', $exception->getPermission());
        $this->assertEquals('Custom access denied message', $exception->getMessage());
    }
}
