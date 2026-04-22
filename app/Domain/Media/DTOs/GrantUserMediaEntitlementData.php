<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\GrantUserMediaEntitlementRequest;

class GrantUserMediaEntitlementData
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        public readonly ?int $featurePackId,
        public readonly string $source,
        public readonly array $config,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
    ) {
    }

    public static function fromRequest(GrantUserMediaEntitlementRequest $request): self
    {
        return new self(
            featurePackId: $request->validated('media_feature_pack_id'),
            source: $request->validated('source', 'admin_grant'),
            config: $request->validated('config', []),
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
        );
    }
}
