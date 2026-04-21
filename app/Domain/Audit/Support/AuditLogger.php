<?php

namespace Domain\Audit\Support;

use Domain\Audit\Actions\StoreAuditLogAction;
use Domain\Audit\DTOs\StoreAuditLogData;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function __construct(
        private readonly StoreAuditLogAction $storeAuditLogAction,
    ) {
    }

    public function log(
        string $event,
        ?int $userId = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
    ): void {
        $this->storeAuditLogAction->execute(
            new StoreAuditLogData(
                userId: $userId,
                event: $event,
                auditable: $auditable,
                oldValues: $this->sanitize($oldValues),
                newValues: $this->sanitize($newValues),
                metadata: $this->sanitize($metadata),
                ipAddress: request()?->ip(),
                userAgent: request()?->userAgent(),
            )
        );
    }

    private function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'remember_token',
            'token',
            'current_password',
            'reset_token',
            'signature',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array($key, $sensitiveKeys, true)) {
                $value = '[REDACTED]';
            }
        });

        return $data;
    }
}
