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

        $optimizer = $this->container->get(DeploymentService::class);
        $stats = $optimizer->optimize();

        // Expect 2 roles processed for cache warming
        $this->assertEquals(2, $stats['roles']);
    }

    public function testDeploymentOptimizerWarmsUserMatrices()
    {
        Vima::roles()->save(new Role('admin'));
        $user = (object)['id' => 42];
        Vima::user($user)->grant()->role('admin');

        $optimizer = $this->container->get(DeploymentService::class);
        $stats = $optimizer->optimize(users: [42, (object)['id' => 99]]);

        $this->assertEquals(2, $stats['users']);
    }
}
