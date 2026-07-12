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

    public function testUserHasRoleBypassWithSuperAdmin()
    {
        $config = \Vima\Core\resolve(\Vima\Core\Config\VimaConfig::class);
        $config->superAdminRole = 'super_admin';
        $config->superAdminBypass = true;

        Vima::roles()->save(new Role('super_admin'));
        Vima::roles()->save(new Role('manager'));
        
        Vima::user($this->user)->grant()->role('super_admin');

        $this->assertTrue(Vima::user($this->user)->has()->role('manager'));
        
        $config->superAdminRole = null;
        $config->superAdminBypass = false;
     }

     public function testUserBulkGrantAndRevoke()
     {
         Vima::roles()->save(new Role('bulk_role_1'));
         Vima::roles()->save(new Role('bulk_role_2'));
         Vima::permissions()->save(new Permission('bulk_perm_1'));
         Vima::permissions()->save(new Permission('bulk_perm_2'));

         // Bulk grant roles and permissions
         Vima::user($this->user)->grant()->role(['bulk_role_1', 'bulk_role_2' => ['context_key' => 'context_val']]);
         Vima::user($this->user)->grant()->permission(['bulk_perm_1', 'bulk_perm_2' => ['scope' => 'admin']]);

         $roles = Vima::user($this->user)->get()->roles();
         $this->assertCount(2, $roles);

         $directPerms = Vima::user($this->user)->get()->permissions()->direct();
         $this->assertCount(2, $directPerms);

         // Bulk revoke
         Vima::user($this->user)->revoke()->role(['bulk_role_1', 'bulk_role_2']);
         Vima::user($this->user)->revoke()->permission(['bulk_perm_1', 'bulk_perm_2']);

         $this->assertEmpty(Vima::user($this->user)->get()->roles());
         $this->assertEmpty(Vima::user($this->user)->get()->permissions()->direct());
     }

     public function testUserBulkDenyAndUndeny()
     {
         Vima::roles()->save(new Role('bulk_deny_role_1'));
         Vima::roles()->save(new Role('bulk_deny_role_2'));
         Vima::permissions()->save(new Permission('bulk_deny_perm_1'));
         Vima::permissions()->save(new Permission('bulk_deny_perm_2'));

         // Bulk deny
         Vima::user($this->user)->deny()->role([
             'bulk_deny_role_1' => 'Violated T&C',
             'bulk_deny_role_2' => ['reason' => 'Banned']
         ]);
         Vima::user($this->user)->deny()->permission([
             'bulk_deny_perm_1' => 'Abuse',
             'bulk_deny_perm_2' => ['reason' => 'Rate limit']
         ]);

         $this->assertTrue(Vima::user($this->user)->is()->denied()->role('bulk_deny_role_1'));
         $this->assertTrue(Vima::user($this->user)->is()->denied()->role('bulk_deny_role_2'));
         $this->assertTrue(Vima::user($this->user)->is()->denied()->permission('bulk_deny_perm_1'));
         $this->assertTrue(Vima::user($this->user)->is()->denied()->permission('bulk_deny_perm_2'));

         // Bulk undeny
         Vima::user($this->user)->undeny()->role(['bulk_deny_role_1', 'bulk_deny_role_2']);
         Vima::user($this->user)->undeny()->permission(['bulk_deny_perm_1', 'bulk_deny_perm_2']);

         $this->assertFalse(Vima::user($this->user)->is()->denied()->role('bulk_deny_role_1'));
         $this->assertFalse(Vima::user($this->user)->is()->denied()->role('bulk_deny_role_2'));
         $this->assertFalse(Vima::user($this->user)->is()->denied()->permission('bulk_deny_perm_1'));
         $this->assertFalse(Vima::user($this->user)->is()->denied()->permission('bulk_deny_perm_2'));
     }
}
