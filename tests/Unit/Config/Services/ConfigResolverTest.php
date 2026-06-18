<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Config\Services;

use PHPUnit\Framework\TestCase;
use Vima\Core\Config\Services\ConfigResolver;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\DTOs\Setup;
use Vima\Core\Config\Contracts\SetupProviderInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;

class DummyProvider implements SetupProviderInterface
{
    public function get(): array
    {
        return [
            'roles' => [
                Role::define('admin', description: 'System Administrator')
                    ->withPermissions(['users.edit', 'system.*'])
                    ->withChildren(['manager']),
                Role::define('manager', description: 'System Manager')
            ],
            'permissions' => [
                Permission::define('users.edit', description: 'Edit any user')
            ]
        ];
    }
}

class ConfigResolverTest extends TestCase
{
    public function testResolverAggregatesAndHydratesEntities()
    {
        $config = new VimaConfig();
        $config->setup = new Setup([DummyProvider::class]);

        $resolver = new ConfigResolver($config);

        $permissions = $resolver->getPermissions();
        $roles = $resolver->getRoles();

        $this->assertCount(2, $permissions);
        $this->assertEquals('Edit any user', $permissions[0]->description); // Hydrated object
        $this->assertEquals('system.*', $permissions[1]->name); // Inferred string

        $this->assertCount(2, $roles);
        $this->assertEquals('admin', $roles[0]->name);
        $managerRole = current(array_filter($roles, fn($r) => $r->name === 'manager'));
        $this->assertNotNull($managerRole);
        $this->assertContains('admin', $managerRole->parents); // Child should map back to parent
    }
}
