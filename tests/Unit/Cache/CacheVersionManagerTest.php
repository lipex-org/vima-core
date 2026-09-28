<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Cache;

use Vima\Core\Tests\TestCase;
use Vima\Core\Cache\Services\CacheVersionManager;
use Vima\Core\Cache\Contracts\CacheInterface;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Vima;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;

class CacheVersionManagerTest extends TestCase
{
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable caching for these unit tests
        $config = new VimaConfig(
            cacheEnabled: true,
            cacheTTL: 3600,
            cachePrefix: 'vima_'
        );
        $this->container->register(VimaConfig::class, $config);

        $this->user = new class implements \Vima\Core\User\Contracts\UserInterface {
            public int $id = 42;
            public function vimaGetId(): string|int { return $this->id; }
        };
    }

    public function testEpochBumpingIncrementsEpochValues(): void
    {
        /** @var CacheVersionManager $versionManager */
        $versionManager = $this->container->get(CacheVersionManager::class);

        $initialGlobal = $versionManager->getGlobalEpoch();
        $nextGlobal = $versionManager->bumpGlobalEpoch();
        $this->assertEquals($initialGlobal + 1, $nextGlobal);
        $this->assertEquals($nextGlobal, $versionManager->getGlobalEpoch());

        $initialUserEpoch = $versionManager->getUserEpoch(42);
        $nextUserEpoch = $versionManager->bumpUserEpoch(42);
        $this->assertEquals($initialUserEpoch + 1, $nextUserEpoch);
        $this->assertEquals($nextUserEpoch, $versionManager->getUserEpoch(42));

        $initialRoleEpoch = $versionManager->getRoleEpoch('editor');
        $nextRoleEpoch = $versionManager->bumpRoleEpoch('editor');
        $this->assertEquals($initialRoleEpoch + 1, $nextRoleEpoch);
        $this->assertEquals($nextRoleEpoch, $versionManager->getRoleEpoch('editor'));
    }

    public function testRoleAndUserMutationsInvalidateCacheProperly(): void
    {
        /** @var CacheVersionManager $versionManager */
        $versionManager = $this->container->get(CacheVersionManager::class);

        $role = Vima::roles()->save(new Role('writer'));
        $perm = Vima::permissions()->save(new Permission('write.article'));
        Vima::role($role)->permissions()->add($perm);
        Vima::user($this->user)->grant()->role($role);

        // Warm cache via matrix and compiled
        $matrix = Vima::user($this->user)->matrix();
        $this->assertArrayHasKey('write.article', $matrix);
        $this->assertTrue($matrix['write.article']);

        // User epoch before mutation
        $userEpochBefore = $versionManager->getUserEpoch(42);

        // Grant new direct permission bumps user epoch
        $perm2 = Vima::permissions()->save(new Permission('publish.article'));
        Vima::user($this->user)->grant()->permission($perm2);

        $this->assertGreaterThan($userEpochBefore, $versionManager->getUserEpoch(42));

        $matrixAfter = Vima::user($this->user)->matrix();
        $this->assertTrue($matrixAfter['publish.article']);
        $this->assertTrue($matrixAfter['write.article']);
    }

    public function testMatrixEvaluationWithFiltersAndWildcards(): void
    {
        $role = Vima::roles()->save(new Role('moderator'));
        $permWildcard = Vima::permissions()->save(new Permission('posts.*'));
        $permOther = Vima::permissions()->save(new Permission('billing.view'));

        Vima::role($role)->permissions()->add($permWildcard);
        Vima::user($this->user)->grant()->role($role);

        // Full matrix
        $fullMatrix = Vima::user($this->user)->matrix();
        $this->assertTrue($fullMatrix['posts.*']);
        $this->assertFalse($fullMatrix['billing.view']);

        // Filtered matrix for frontend UI checks
        $filtered = Vima::user($this->user)->matrix([
            'posts.create',
            'posts.delete',
            'billing.view'
        ]);

        $this->assertTrue($filtered['posts.create']);
        $this->assertTrue($filtered['posts.delete']);
        $this->assertFalse($filtered['billing.view']);
    }

    public function testDeploymentClearOnlyBumpsGlobalEpochWithoutWipingAppCache(): void
    {
        /** @var CacheInterface $cache */
        $cache = $this->container->get(CacheInterface::class);
        /** @var CacheVersionManager $versionManager */
        $versionManager = $this->container->get(CacheVersionManager::class);

        // Store non-Vima custom app data in the same cache store
        $cache->set('app_user_session_999', 'active_session_data', 3600);
        $this->assertEquals('active_session_data', $cache->get('app_user_session_999'));

        $globalEpochBefore = $versionManager->getGlobalEpoch();

        // Clear deployment/vima cache
        $deploymentService = $this->container->get(\Vima\Core\Support\Deployment\Services\DeploymentService::class);
        $deploymentService->clear();

        // Global epoch increased
        $this->assertGreaterThan($globalEpochBefore, $versionManager->getGlobalEpoch());

        // App third-party data is still preserved!
        $this->assertEquals('active_session_data', $cache->get('app_user_session_999'));
    }
}
