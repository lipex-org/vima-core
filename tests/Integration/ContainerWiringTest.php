<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Integration;

use Vima\Core\Tests\TestCase;
use Vima\Core\Vima;

class ContainerWiringTest extends TestCase
{
    public function testCoreBootstrapperResolvesVimaFacade()
    {
        // Tests that all services and their mock dependencies resolve correctly
        
        $roleService = Vima::roles();
        $this->assertInstanceOf(\Vima\Core\Role\Services\RoleService::class, $roleService);

        $permissionService = Vima::permissions();
        $this->assertInstanceOf(\Vima\Core\Permission\Services\PermissionService::class, $permissionService);

        $authService = Vima::auth();
        $this->assertInstanceOf(\Vima\Core\AuthorizationService::class, $authService);
    }
}
