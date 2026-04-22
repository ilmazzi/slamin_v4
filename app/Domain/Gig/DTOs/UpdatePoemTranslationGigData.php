<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdatePoemTranslationGigRequest;
use Domain\Gig\Enums\GigStatus;

class UpdatePoemTranslationGigData
{
    public function __construct(
        public readonly ?string $targetLanguage,
        public readonly bool $targetLanguageProvided,
        public readonly ?string $proposedPrice,
        public readonly bool $proposedPriceProvided,
        public readonly ?string $currency,
        public readonly bool $currencyProvided,
        public readonly ?string $deadline,
        public readonly bool $deadlineProvided,
        public readonly ?string $specialRequirements,
        public readonly bool $specialRequirementsProvided,
        public readonly ?GigStatus $status,
    ) {
    }

    public static function fromRequest(UpdatePoemTranslationGigRequest $request): self
    {
        return new self(
            targetLanguage: $request->filled('target_language') ? $request->string('target_language')->toString() : null,
            targetLanguageProvided: $request->exists('target_language'),
            proposedPrice: $request->filled('proposed_price') ? (string) $request->input('proposed_price') : null,
            proposedPriceProvided: $request->exists('proposed_price'),
            currency: $request->filled('currency') ? strtoupper($request->string('currency')->toString()) : null,
            currencyProvided: $request->exists('currency'),
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            deadlineProvided: $request->exists('deadline'),
            specialRequirements: $request->filled('special_requirements') ? $request->string('special_requirements')->toString() : null,
            specialRequirementsProvided: $request->exists('special_requirements'),
            status: $request->filled('status') ? GigStatus::from($request->string('status')->toString()) : null,
        );
    }
}
