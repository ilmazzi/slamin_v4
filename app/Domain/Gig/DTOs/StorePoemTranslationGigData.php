<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StorePoemTranslationGigRequest;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;

class StorePoemTranslationGigData
{
    public function __construct(
        public readonly string $targetLanguage,
        public readonly string $proposedPrice,
        public readonly string $currency,
        public readonly ?string $deadline,
        public readonly ?string $specialRequirements,
        public readonly GigStatus $status,
    ) {
    }

    public static function fromRequest(StorePoemTranslationGigRequest $request): self
    {
        return new self(
            targetLanguage: $request->string('target_language')->toString(),
            proposedPrice: $request->input('proposed_price'),
            currency: strtoupper($request->input('currency', 'EUR')),
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            specialRequirements: $request->filled('special_requirements') ? $request->string('special_requirements')->toString() : null,
            status: GigStatus::from($request->input('status', GigStatus::Open->value)),
        );
    }
}
