<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StoreEventGigRequest;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;

class StoreGigData
{
    public function __construct(
        public readonly GigType $type,
        public readonly int $quantity,
        public readonly ?string $language,
        public readonly bool $hospitalityRequired,
        public readonly ?string $description,
        public readonly GigStatus $status,
    ) {
    }

    public static function fromRequest(StoreEventGigRequest $request): self
    {
        return new self(
            type: GigType::from($request->string('type')->toString()),
            quantity: $request->integer('quantity'),
            language: $request->filled('language') ? $request->string('language')->toString() : null,
            hospitalityRequired: $request->boolean('hospitality_required'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            status: GigStatus::from($request->input('status', GigStatus::Open->value)),
        );
    }
}
