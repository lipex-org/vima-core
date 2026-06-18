<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Policy;

use Vima\Core\Tests\TestCase;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\Policy\Contracts\PolicyInterface;
use Vima\Core\Policy\DTOs\AccessContext;

class DummyResource {}

class DummyPolicy implements PolicyInterface
{
    public static function getResource(): string
    {
        return DummyResource::class;
    }

    public function canEdit(AccessContext $context, DummyResource $resource): bool
    {
        return $context->user->id === 1; // Only user 1 can edit
    }
}

class PolicyRegistryTest extends TestCase
{
    private PolicyRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->container->get(PolicyRegistry::class);
    }

    public function testRegisterAndEvaluateClosurePolicy()
    {
        $this->registry->register('posts.view', function(AccessContext $context) {
            return $context->user->id === 5;
        });

        $user5 = (object)['id' => 5];
        $user6 = (object)['id' => 6];

        $this->assertTrue($this->registry->evaluate($user5, 'posts.view'));
        $this->assertFalse($this->registry->evaluate($user6, 'posts.view'));
    }

    public function testRegisterClassBasedPolicy()
    {
        $this->registry->registerClass(DummyResource::class, DummyPolicy::class);
        
        $user1 = (object)['id' => 1];
        $user2 = (object)['id' => 2];
        $resource = new DummyResource();

        $this->assertTrue($this->registry->evaluate($user1, 'edit', $resource));
        $this->assertFalse($this->registry->evaluate($user2, 'edit', $resource));
    }

    public function testHasPolicy()
    {
        $this->registry->register('some.action', fn() => true);
        
        $this->assertTrue($this->registry->has('some.action'));
        $this->assertFalse($this->registry->has('missing.action'));
    }
}
