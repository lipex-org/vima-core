<?php

namespace Vima\Core\Audit\Services;

use Vima\Core\Audit\Contracts\AuditRepositoryInterface;
use Vima\Core\Events\Access\AuthorizationChecked;
use Vima\Core\Config\VimaConfig;
use Vima\Core\User\Services\UserResolutionService;

class AuditService
{
    public function __construct(
        private AuditRepositoryInterface $repository,
        private VimaConfig $config,
        private UserResolutionService $userResolution
    ) {}

    public function handleAuthorizationChecked(AuthorizationChecked $event): void
    {
        $data = $event->getData();
        $cols = $this->config->columns->auditLogs;

        $userId = $data['userId'] ?? ($data['user'] ? $this->userResolution->resolveId($data['user']) : null);
        
        $arguments = $data['arguments'] ?? [];
        // Sanitize arguments for JSON
        $serializedArgs = json_encode(array_map(fn($a) => is_object($a) ? get_class($a) : $a, $arguments));

        $this->repository->log([
            $cols->userId => $userId,
            $cols->permission => $data['permission'],
            $cols->namespace => $data['namespace'],
            $cols->result => $data['result'] ? 1 : 0,
            $cols->reason => $data['reason'] ?? null,
            $cols->arguments => $serializedArgs,
            $cols->createdAt => date('Y-m-d H:i:s')
        ]);
    }
}
