<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Integration;

use Vima\Core\Tests\TestCase;
use Vima\Core\Support\Deployment\Services\DeploymentService;
use Vima\Core\Vima;
use Vima\Core\Role\Entities\Role;

class DeploymentServiceTest extends TestCase
{
    public function testDeploymentOptimizerWarmsCaches()
    {
        // 1. Create a dummy role structure
        Vima::roles()->save(new Role('child'));

        $parent = Vima::roles()->save(new Role('parent'));
        Vima::role('child')->parents()->add($parent->id);

        // 2. We skip dummy policies to avoid testing Reflection mapping logic since
        // the PolicyRegistry cache is disabled without configuration.

        $optimizer = $this->container->get(DeploymentService::class);
        $stats = $optimizer->optimize();

        // Expect 2 roles processed for cache warming
        $this->assertEquals(2, $stats['roles']);
    }
}
