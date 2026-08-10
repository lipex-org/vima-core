<?php

namespace Vima\Core\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Vima\Core\Exceptions\AccessDeniedException;
use Vima\Core\Exceptions\AccessDeniedExceptionInterface;

class AccessDeniedExceptionTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the factory
        AccessDeniedException::useFactory(null);
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

        AccessDeniedException::useFactory(fn($permission, $user, $resolver) => $customException);

        $exception = AccessDeniedException::forPermission('custom.permission');

        $this->assertInstanceOf(AccessDeniedExceptionInterface::class, $exception);
        $this->assertEquals('custom.permission', $exception->getPermission());
        $this->assertEquals('Custom access denied message', $exception->getMessage());
    }
}
