<?php

namespace Vima\Core\Config\Entities\Sync;

final class SyncResponse
{
    public function __construct(
        public readonly Skipped $skipped,
        public readonly bool $warn,
    ) {
    }

    public function shouldWarn(): bool
    {
        return $this->warn;
    }
}
