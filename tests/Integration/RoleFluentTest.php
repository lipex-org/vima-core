<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Integration;

use Vima\Core\Tests\TestCase;
use Vima\Core\Vima;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;

class RoleFluentTest extends TestCase
{
    public function testRoleFluentPermissionsBuilder()
    {
        $role = Vima::roles()->save(new Role('editor'));
        $perm1 = Vima::permissions()->save(new Permission('posts.edit'));
        $perm2 = Vima::permissions()->save(new Permission('posts.create'));

        Vima::role('editor')
            ->permissions()
            ->add($perm1->id)
            ->add($perm2->id);

        $rolePerms = Vima::role('editor')->permissions()->all();

        $this->assertCount(2, $rolePerms);
        $this->assertEquals($perm1->id, $rolePerms[0]->permissionId);

        Vima::role('editor')->permissions()->remove($perm1->id);

        $rolePermsAfter = Vima::role('editor')->permissions()->all();
        $this->assertCount(1, $rolePermsAfter);
        $this->assertEquals($perm2->id, $rolePermsAfter[0]->permissionId);
    }

    public function testRoleFluentParentsBuilder()
    {
        $child = Vima::roles()->save(new Role('child'));
        $parent1 = Vima::roles()->save(new Role('parent1'));
        $parent2 = Vima::roles()->save(new Role('parent2'));

        Vima::role('child')
            ->parents()->add($parent1->id)
            ->add($parent2->id);

        $parents = Vima::role('child')->parents()->all();

        $this->assertCount(2, $parents);
        $this->assertEquals($parent1->id, $parents[0]->parentId);

        Vima::role('child')->parents()->clear();

        $this->assertEmpty(Vima::role('child')->parents()->all());
    }
}
