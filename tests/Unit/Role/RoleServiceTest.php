<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Role;

use Vima\Core\Tests\TestCase;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Role\Fluent\RoleResource;

class RoleServiceTest extends TestCase
{
    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(RoleService::class);
    }

    public function testCreateAndFindRole()
    {
        $role = new Role('admin', description: 'System Admin');
        $saved = $this->service->save($role);

        $this->assertNotNull($saved->id);
        $this->assertEquals('admin', $saved->name);

        $found = $this->service->find('admin');
        $this->assertEquals($saved->id, $found->id);
    }

    public function testRoleMethodReturnsRoleResource()
    {
        $this->service->save(new Role('editor'));

        $resource = $this->service->role('editor');
        $this->assertInstanceOf(RoleResource::class, $resource);
        $this->assertEquals('editor', $resource->getOriginalRole()->name);
    }

    public function testRoleMethodThrowsIfNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->role('non_existent');
    }

    public function testDeleteRole()
    {
        $role = $this->service->save(new Role('moderator'));
        $this->assertNotNull($this->service->find('moderator'));

        $this->service->delete($role);
        $this->assertNull($this->service->find('moderator'));
    }
}
