<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Config\Services;

use Vima\Core\Tests\TestCase;
use Vima\Core\Config\Services\SyncService;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Config\DTOs\Setup;
use Vima\Core\Config\Contracts\SetupProviderInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;

class SyncDummyProvider implements SetupProviderInterface
{
    public function get(): array
    {
        return [
            'roles' => [
                Role::define('author', description: 'Can write')
                    ->withPermissions(['posts.write'])
                    ->withParents(['guest']),
                Role::define('guest', description: 'Can view')
            ],
            'permissions' => [
                Permission::define('posts.write', description: 'Write posts')
            ]
        ];
    }
}

class SyncServiceTest extends TestCase
{
    public function testSyncPopulatesRepositoriesSuccessfully()
    {
        $config = $this->container->get(VimaConfig::class);
        $config->setup = new Setup([SyncDummyProvider::class]);

        $syncService = $this->container->get(SyncService::class);
        
        $response = $syncService->sync();
        $this->assertFalse($response->shouldWarn());

        // Verify Roles
        $rolesRepo = $this->container->get(\Vima\Core\Role\Contracts\RoleRepositoryInterface::class);
        $this->assertCount(2, $rolesRepo->all());
        
        $author = $rolesRepo->findByName('author');
        $this->assertNotNull($author);
        $this->assertEquals('Can write', $author->description);

        // Verify Permissions
        $permsRepo = $this->container->get(\Vima\Core\Permission\Contracts\PermissionRepositoryInterface::class);
        $this->assertCount(1, $permsRepo->all());
        
        $writePerm = $permsRepo->findByName('posts.write');
        $this->assertNotNull($writePerm);
        $this->assertEquals('Write posts', $writePerm->description);

        // Verify Role-Permission mapping
        $rolePermsRepo = $this->container->get(\Vima\Core\Role\Contracts\RolePermissionRepositoryInterface::class);
        $authorPerms = $rolePermsRepo->getRolePermissions($author);
        $this->assertCount(1, $authorPerms);
        $this->assertEquals($writePerm->id, $authorPerms[0]->permissionId);

        // Verify Role-Parent mapping
        $roleParentsRepo = $this->container->get(\Vima\Core\Role\Contracts\RoleParentRepositoryInterface::class);
        $authorParents = $roleParentsRepo->getParents($author);
        $guest = $rolesRepo->findByName('guest');
        
        $this->assertCount(1, $authorParents);
        $this->assertEquals($guest->id, $authorParents[0]->parentId);
    }
}
