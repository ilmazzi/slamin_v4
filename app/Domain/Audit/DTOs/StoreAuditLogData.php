<?php

namespace Domain\Audit\DTOs;

use Illuminate\Database\Eloquent\Model;

class StoreAuditLogData
{
    public function __construct(
        public readonly ?int $userId,
        public readonly string $event,
        public readonly ?Model $auditable = null,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
        public readonly ?array $metadata = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {
    }
}
