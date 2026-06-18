<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Integration;

use Vima\Core\Tests\TestCase;
use Vima\Core\Vima;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;

class UserFluentTest extends TestCase
{
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new class implements \Vima\Core\User\Contracts\UserInterface {
            public int $id = 1;
            public function vimaGetId(): string|int { return $this->id; }
        };
    }

    public function testUserGrantAndRevokeRolesAndPermissions()
    {
        Vima::roles()->save(new Role('admin'));
        Vima::permissions()->save(new Permission('manage_users'));

        Vima::user($this->user)->grant()->role('admin');
        Vima::user($this->user)->grant()->permission('manage_users', ['scope' => 'all']);

        $roles = Vima::user($this->user)->get()->roles();
        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles[0]->name);

        $directPerms = Vima::user($this->user)->get()->permissions()->direct();
        $this->assertCount(1, $directPerms);
        $this->assertEquals('manage_users', $directPerms[0]->name);
        $this->assertEquals(['scope' => 'all'], $directPerms[0]->constraints);

        Vima::user($this->user)->revoke()->role('admin');
        Vima::user($this->user)->revoke()->permission('manage_users');

        $this->assertEmpty(Vima::user($this->user)->get()->roles());
        $this->assertEmpty(Vima::user($this->user)->get()->permissions()->direct());
    }

    public function testUserExplicitDenyRoleAndPermission()
    {
        Vima::roles()->save(new Role('banned_role'));
        Vima::permissions()->save(new Permission('post.comment'));

        Vima::user($this->user)->deny()->role('banned_role', 'Violation of terms');
        Vima::user($this->user)->deny()->permission('post.comment', 'Spamming');

        $this->assertTrue(Vima::user($this->user)->is()->denied()->role('banned_role'));
        $this->assertTrue(Vima::user($this->user)->is()->denied()->permission('post.comment'));

        $roleDenies = Vima::user($this->user)->get()->denies()->role();
        $this->assertCount(1, $roleDenies);
        $this->assertEquals('Violation of terms', $roleDenies[0]->reason);

        Vima::user($this->user)->undeny()->role('banned_role');
        Vima::user($this->user)->undeny()->permission('post.comment');

        $this->assertFalse(Vima::user($this->user)->is()->denied()->role('banned_role'));
        $this->assertFalse(Vima::user($this->user)->is()->denied()->permission('post.comment'));
    }

    public function testUserHasRole()
    {
        Vima::roles()->save(new Role('manager'));
        Vima::user($this->user)->grant()->role('manager');

        $this->assertTrue(Vima::user($this->user)->has()->role('manager'));
        $this->assertFalse(Vima::user($this->user)->has()->role('non_existent'));
    }
}
