<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\StoreMediaCheckoutOrderRequest;

class StoreMediaCheckoutOrderData
{
    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public readonly int $featurePackId,
        public readonly ?string $provider,
        public readonly array $payload,
    ) {
    }

    public static function fromRequest(StoreMediaCheckoutOrderRequest $request): self
    {
        return new self(
            featurePackId: (int) $request->validated('media_feature_pack_id'),
            provider: $request->validated('provider'),
            payload: $request->validated('payload', []),
        );
    }
}
