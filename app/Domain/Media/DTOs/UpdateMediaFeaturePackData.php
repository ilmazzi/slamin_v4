<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\UpdateMediaFeaturePackRequest;

class UpdateMediaFeaturePackData
{
    /**
     * @param array<string,mixed>|null $config
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?array $config,
        public readonly ?bool $isActive,
        public readonly ?int $priceAmount,
        public readonly ?string $currency,
    ) {
    }

    public static function fromRequest(UpdateMediaFeaturePackRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->validated('name') : null,
            description: $request->has('description') ? $request->validated('description') : null,
            config: $request->has('config') ? $request->validated('config') : null,
            isActive: $request->has('is_active') ? (bool) $request->validated('is_active') : null,
            priceAmount: $request->has('price_amount') ? $request->validated('price_amount') : null,
            currency: $request->has('currency') ? $request->validated('currency') : null,
        );
    }
}
