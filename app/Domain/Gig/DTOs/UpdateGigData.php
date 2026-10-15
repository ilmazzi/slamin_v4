<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdateEventGigRequest;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;

class UpdateGigData
{
    public function __construct(
        public readonly ?GigType $type,
        public readonly ?int $quantity,
        public readonly ?string $language,
        public readonly bool $languageProvided,
        public readonly ?bool $hospitalityRequired,
        public readonly ?string $description,
        public readonly bool $descriptionProvided,
        public readonly ?GigStatus $status,
        public readonly array $offerAttributes = [],
    ) {}

    private const OFFER_FIELDS = [
        'proposed_price',
        'currency',
        'deadline',
        'special_requirements',
        'cachet_enabled',
        'travel_expenses_enabled',
        'travel_expenses_max',
        'accommodation_enabled',
        'accommodation_details',
    ];

    public static function fromRequest(UpdateEventGigRequest $request): self
    {
        return new self(
            type: $request->filled('type') ? GigType::from($request->string('type')->toString()) : null,
            quantity: $request->filled('quantity') ? $request->integer('quantity') : null,
            language: $request->filled('language') ? $request->string('language')->toString() : null,
            languageProvided: $request->exists('language'),
            hospitalityRequired: $request->exists('hospitality_required') ? $request->boolean('hospitality_required') : null,
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            descriptionProvided: $request->exists('description'),
            status: $request->filled('status') ? GigStatus::from($request->string('status')->toString()) : null,
            offerAttributes: array_intersect_key($request->validated(), array_flip(self::OFFER_FIELDS)),
        );
    }
}
