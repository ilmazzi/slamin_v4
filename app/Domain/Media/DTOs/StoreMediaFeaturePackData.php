<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\StoreMediaFeaturePackRequest;

class StoreMediaFeaturePackData
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $config,
        public readonly bool $isActive,
        public readonly ?int $priceAmount,
        public readonly ?string $currency,
    ) {
    }

    public static function fromRequest(StoreMediaFeaturePackRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            config: $request->validated('config', []),
            isActive: (bool) $request->validated('is_active', true),
            priceAmount: $request->validated('price_amount'),
            currency: $request->validated('currency'),
        );
    }
}
