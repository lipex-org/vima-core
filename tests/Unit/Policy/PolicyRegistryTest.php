<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Policy;

use Vima\Core\Tests\TestCase;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\Policy\Contracts\PolicyInterface;
use Vima\Core\Policy\DTOs\AccessContext;

use Vima\Core\Policy\Attributes\MapToPermission;

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

    #[MapToPermission('publish')]
    public function customPublishMethod(AccessContext $context, DummyResource $resource): bool
    {
        return $context->user->id === 10;
    }

    #[MapToPermission('dummy.delete')]
    public function customDeleteMethod(AccessContext $context, DummyResource $resource): bool
    {
        return $context->user->id === 20;
    }

    #[MapToPermission('archive', 'tenant_1')]
    public function customArchiveMethod(AccessContext $context, DummyResource $resource): bool
    {
        return $context->user->id === 30;
    }

    #[MapToPermission('export')]
    #[MapToPermission('download')]
    public function customExportMethod(AccessContext $context, DummyResource $resource): bool
    {
        return $context->user->id === 40;
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

    public function testMapToPermissionAttributeMatching()
    {
        $this->registry->registerClass(DummyResource::class, DummyPolicy::class);
        $resource = new DummyResource();

        $user10 = (object)['id' => 10];
        $user20 = (object)['id' => 20];
        $user30 = (object)['id' => 30];
        $user40 = (object)['id' => 40];
        $otherUser = (object)['id' => 99];

        // 1. #[MapToPermission('publish')] matched with action 'publish' and full 'dummy.publish'
        $this->assertTrue($this->registry->evaluate($user10, 'publish', $resource));
        $this->assertTrue($this->registry->evaluate($user10, 'dummy.publish', $resource));
        $this->assertFalse($this->registry->evaluate($otherUser, 'dummy.publish', $resource));

        // 2. #[MapToPermission('dummy.delete')] matched with 'dummy.delete' and action 'delete'
        $this->assertTrue($this->registry->evaluate($user20, 'dummy.delete', $resource));
        $this->assertTrue($this->registry->evaluate($user20, 'delete', $resource));
        $this->assertFalse($this->registry->evaluate($otherUser, 'delete', $resource));

        // 3. #[MapToPermission('archive', 'tenant_1')] matched with 'tenant_1:dummy.archive' and 'tenant_1:archive'
        $this->assertTrue($this->registry->evaluate($user30, 'tenant_1:dummy.archive', $resource));
        $this->assertTrue($this->registry->evaluate($user30, 'tenant_1:archive', $resource));
        $this->assertFalse($this->registry->evaluate($otherUser, 'tenant_1:archive', $resource));

        // 4. Multiple attributes: #[MapToPermission('export')] and #[MapToPermission('download')]
        $this->assertTrue($this->registry->evaluate($user40, 'export', $resource));
        $this->assertTrue($this->registry->evaluate($user40, 'download', $resource));
        $this->assertTrue($this->registry->evaluate($user40, 'dummy.export', $resource));
        $this->assertTrue($this->registry->evaluate($user40, 'dummy.download', $resource));
        $this->assertFalse($this->registry->evaluate($otherUser, 'dummy.export', $resource));
    }

    public function testHasPolicy()
    {
        $this->registry->register('some.action', fn() => true);
        
        $this->assertTrue($this->registry->has('some.action'));
        $this->assertFalse($this->registry->has('missing.action'));
    }

    public function testWarmCacheAndClearCache()
    {
        $this->registry->registerClass(DummyResource::class, DummyPolicy::class);

        $stats = $this->registry->warmCache(force: true);

        $this->assertArrayHasKey(DummyPolicy::class, $stats);
        $this->assertGreaterThan(0, $stats[DummyPolicy::class]);

        // Clear cache
        $this->registry->clearCache();

        // Should still be able to evaluate via reflection rebuild
        $user10 = (object)['id' => 10];
        $this->assertTrue($this->registry->evaluate($user10, 'publish', new DummyResource()));
    }

    public function testDiscoveredPoliciesLoadedFromConfig()
    {
        $config = new \Vima\Core\Config\VimaConfig(
            policy: new \Vima\Core\Config\DTOs\PolicyConfig(
                registered: [],
                discovered: [DummyPolicy::class]
            )
        );

        $registry = new PolicyRegistry(
            $this->container->get(\Vima\Core\Events\Contracts\EventDispatcherInterface::class),
            $this->container->get(\Vima\Core\Cache\Contracts\CacheInterface::class),
            $config
        );

        $this->assertArrayHasKey(DummyResource::class, $registry->getRegisteredClasses());
        $this->assertEquals(DummyPolicy::class, $registry->getRegisteredClasses()[DummyResource::class]);

        $user10 = (object)['id' => 10];
        $this->assertTrue($registry->evaluate($user10, 'dummy.publish', new DummyResource()));
    }

    public function testDiscoveredPoliciesLoadedFromCache()
    {
        $cache = $this->container->get(\Vima\Core\Cache\Contracts\CacheInterface::class);
        $cache->set('vima_policies_discovered', [DummyPolicy::class], 3600);

        $config = new \Vima\Core\Config\VimaConfig(
            cacheEnabled: true,
            policy: new \Vima\Core\Config\DTOs\PolicyConfig(
                registered: [],
                discovered: []
            )
        );

        $registry = new PolicyRegistry(
            $this->container->get(\Vima\Core\Events\Contracts\EventDispatcherInterface::class),
            $cache,
            $config
        );

        $this->assertArrayHasKey(DummyResource::class, $registry->getRegisteredClasses());
        $this->assertEquals(DummyPolicy::class, $registry->getRegisteredClasses()[DummyResource::class]);
    }
}
