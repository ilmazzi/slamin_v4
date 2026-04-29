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
        public readonly ?float $proposedPrice,
        public readonly ?string $currency,
        public readonly ?string $deadline,
        public readonly ?string $specialRequirements,
        public readonly bool $cachetEnabled,
        public readonly bool $travelExpensesEnabled,
        public readonly ?float $travelExpensesMax,
        public readonly bool $accommodationEnabled,
        public readonly ?string $accommodationDetails,
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
            proposedPrice: $request->filled('proposed_price') ? (float) $request->input('proposed_price') : null,
            currency: $request->filled('currency') ? $request->string('currency')->toString() : null,
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            specialRequirements: $request->filled('special_requirements') ? $request->string('special_requirements')->toString() : null,
            cachetEnabled: $request->boolean('cachet_enabled'),
            travelExpensesEnabled: $request->boolean('travel_expenses_enabled'),
            travelExpensesMax: $request->filled('travel_expenses_max') ? (float) $request->input('travel_expenses_max') : null,
            accommodationEnabled: $request->boolean('accommodation_enabled'),
            accommodationDetails: $request->filled('accommodation_details') ? $request->string('accommodation_details')->toString() : null,
            status: GigStatus::from($request->input('status', GigStatus::Open->value)),
        );
    }
}
