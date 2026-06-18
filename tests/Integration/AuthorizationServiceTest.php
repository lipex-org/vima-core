<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Integration;

use Vima\Core\Tests\TestCase;
use Vima\Core\Vima;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Policy\DTOs\AccessContext;

class DummyPost {}

class AuthorizationServiceTest extends TestCase
{
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new class implements \Vima\Core\User\Contracts\UserInterface {
            public int $id = 99;
            public function vimaGetId(): string|int { return $this->id; }
        };
    }

    public function testRbacDirectPermission()
    {
        Vima::permissions()->create('direct.action');
        Vima::user($this->user)->grant()->permission('direct.action');

        $this->assertTrue(Vima::auth()->isPermitted($this->user, 'direct.action'));
        $this->assertFalse(Vima::auth()->isPermitted($this->user, 'missing.action'));
    }

    public function testRbacRolePermission()
    {
        Vima::roles()->save(new Role('member'));
        $perm = Vima::permissions()->create('role.action');
        
        Vima::role('member')->permissions()->add($perm->id);
        Vima::user($this->user)->grant()->role('member');

        $this->assertTrue(Vima::auth()->isPermitted($this->user, 'role.action'));
        $this->assertFalse(Vima::auth()->isPermitted($this->user, 'unrelated.action'));
    }

    public function testAbacPolicyEvaluation()
    {
        Vima::policies()->register('post.update', function(AccessContext $ctx, DummyPost $post) {
            return $ctx->user->vimaGetId() === 99;
        });

        $post = new DummyPost();

        // RBAC should be false, ABAC true
        $this->assertTrue(Vima::auth()->can($this->user, 'post.update', $post));

        $otherUser = new class implements \Vima\Core\User\Contracts\UserInterface {
            public int $id = 1;
            public function vimaGetId(): string|int { return $this->id; }
        };
        $this->assertFalse(Vima::auth()->can($otherUser, 'post.update', $post));
    }

    public function testEnforceThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access Denied to strict.action');

        Vima::auth()->enforce($this->user, 'strict.action');
    }
}
