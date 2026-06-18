<?php

namespace Vima\Core\Audit\Contracts;

/**
 * Interface AuditRepositoryInterface
 */
use Vima\Core\Audit\Entities\BareAuditLog;

/**
 * Interface AuditRepositoryInterface
 */
interface AuditRepositoryInterface
{
    /**
     * Log an access check.
     *
     * @param BareAuditLog|array $data
     * @return void
     */
    public function log(BareAuditLog|array $data): void;
}
