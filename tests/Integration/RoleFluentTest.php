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
            ->add($perm1)
            ->add($perm2);

        $rolePerms = Vima::role('editor')->permissions()->all();

        $this->assertCount(2, $rolePerms);
        $this->assertEquals($perm1->id, $rolePerms[0]->id);

        Vima::role('editor')->permissions()->remove($perm1);

        $rolePermsAfter = Vima::role('editor')->permissions()->all();
        $this->assertCount(1, $rolePermsAfter);
        $this->assertEquals($perm2->id, $rolePermsAfter[0]->id);
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

    public function testRoleFluentBulkPermissions()
    {
        $role = Vima::roles()->save(new Role('bulk_role'));
        $perm1 = Vima::permissions()->save(new Permission('bulk.one'));
        $perm2 = Vima::permissions()->save(new Permission('bulk.two'));
        $perm3 = Vima::permissions()->save(new Permission('bulk.three'));

        // Bulk add with string, Permission object, and constraints
        Vima::role('bulk_role')
            ->permissions()
            ->add([
                'bulk.one',
                $perm2,
                'bulk.three' => ['scope' => 'custom']
            ]);

        $rolePerms = Vima::role('bulk_role')->permissions()->all();
        $this->assertCount(3, $rolePerms);

        // Verify the constraints were applied correctly
        foreach ($rolePerms as $rp) {
            if ($rp->name === 'bulk.three') {
                $this->assertEquals(['scope' => 'custom'], $rp->constraints);
            }
        }

        // Bulk remove
        Vima::role('bulk_role')->permissions()->remove(['bulk.one', $perm2]);

        $rolePermsAfter = Vima::role('bulk_role')->permissions()->all();
        $this->assertCount(1, $rolePermsAfter);
        $this->assertEquals('bulk.three', $rolePermsAfter[0]->name);
    }
}
