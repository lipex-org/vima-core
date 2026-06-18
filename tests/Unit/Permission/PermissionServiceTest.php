<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Permission;

use Vima\Core\Tests\TestCase;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Permission\Entities\Permission;

class PermissionServiceTest extends TestCase
{
    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(PermissionService::class);
    }

    public function testCreatePermission()
    {
        $permission = $this->service->create('posts.edit', 'Can edit posts');

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('posts.edit', $permission->name);
        $this->assertEquals('Can edit posts', $permission->description);
        $this->assertNotNull($permission->id);
    }

    public function testCreateNamespacedPermission()
    {
        $permission = $this->service->create('tenant_a:posts.edit');

        $this->assertEquals('posts.edit', $permission->name);
        $this->assertEquals('tenant_a', $permission->namespace);
        $this->assertEquals('tenant_a:posts.edit', $permission->getFullName());
    }

    public function testFindPermissionById()
    {
        $created = $this->service->create('posts.delete');
        $found = $this->service->find($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals('posts.delete', $found->name);
    }

    public function testFindPermissionByName()
    {
        $this->service->create('posts.view');
        $found = $this->service->find('posts.view');

        $this->assertNotNull($found);
        $this->assertEquals('posts.view', $found->name);
    }

    public function testFindPermissionByNamespacedName()
    {
        $this->service->create('tenant_b:posts.view');
        $found = $this->service->find('tenant_b:posts.view');

        $this->assertNotNull($found);
        $this->assertEquals('posts.view', $found->name);
        $this->assertEquals('tenant_b', $found->namespace);
    }

    public function testDeletePermission()
    {
        $permission = $this->service->create('posts.draft');
        $this->assertNotNull($this->service->find('posts.draft'));

        $this->service->delete($permission);
        $this->assertNull($this->service->find('posts.draft'));
    }
}
