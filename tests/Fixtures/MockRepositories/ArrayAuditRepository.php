<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\Audit\Contracts\AuditRepositoryInterface;
use Vima\Core\Audit\Entities\BareAuditLog;

class ArrayAuditRepository implements AuditRepositoryInterface
{
    public array $logs = [];
    private int $nextId = 1;

    public function log(BareAuditLog|array $data): void
    {
        $log = is_array($data) ? new BareAuditLog(...$data) : $data;
        if ($log->id === null) {
            $log->id = $this->nextId++;
        }
        $this->logs[$log->id] = clone $log;
    }
}
