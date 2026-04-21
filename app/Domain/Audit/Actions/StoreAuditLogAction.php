<?php

namespace Domain\Audit\Actions;

use Domain\Audit\DTOs\StoreAuditLogData;
use Domain\Audit\Models\AuditLog;

class StoreAuditLogAction
{
    public function execute(StoreAuditLogData $data): AuditLog
    {
        return AuditLog::create([
            'user_id' => $data->userId,
            'event' => $data->event,
            'auditable_type' => $data->auditable?->getMorphClass(),
            'auditable_id' => $data->auditable?->getKey(),
            'old_values' => $data->oldValues,
            'new_values' => $data->newValues,
            'metadata' => $data->metadata,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent,
        ]);
    }
}
