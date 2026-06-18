<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Unit\Audit;

use Vima\Core\Tests\TestCase;
use Vima\Core\Audit\Services\AuditService;
use Vima\Core\Events\Access\AuthorizationChecked;
use Vima\Core\Audit\Contracts\AuditRepositoryInterface;

class AuditServiceTest extends TestCase
{
    public function testAuditServiceLogsAuthorizationEvents()
    {
        $repo = $this->container->get(AuditRepositoryInterface::class);
        $service = $this->container->get(AuditService::class);

        $dummyUser = new class implements \Vima\Core\User\Contracts\UserInterface {
            public function vimaGetId(): string|int { return 5; }
        };

        $event = new AuthorizationChecked(
            user: $dummyUser,
            permission: 'posts.edit',
            result: true,
            namespace: 'tenant_a',
            arguments: ['post_id' => 1],
            reason: 'RBAC match'
        );

        $service->handleAuthorizationChecked($event);

        // Fetch logs directly from the Array repo
        $logs = $repo->logs;
        $this->assertCount(1, $logs);
        
        $log = reset($logs);
        $this->assertEquals(5, $log->user_id);
        $this->assertEquals('posts.edit', $log->permission);
        $this->assertEquals(1, $log->result);
        $this->assertEquals('tenant_a', $log->namespace);
        $this->assertEquals('RBAC match', $log->reason);
        $this->assertEquals('{"post_id":1}', $log->arguments);
    }
}
