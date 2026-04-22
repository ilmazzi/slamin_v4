<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdatePoemTranslationGigData;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdatePoemTranslationGigAction
{
    public function execute(Gig $gig, UpdatePoemTranslationGigData $data): Gig
    {
        if ($data->targetLanguageProvided && $data->targetLanguage !== null) {
            $duplicate = Gig::query()
                ->where('id', '!=', $gig->id)
                ->where('giggable_type', $gig->giggable_type)
                ->where('giggable_id', $gig->giggable_id)
                ->where('type', GigType::Translator->value)
                ->where('language', $data->targetLanguage)
                ->whereIn('status', [GigStatus::Draft->value, GigStatus::Open->value, GigStatus::Filled->value])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'target_language' => [__('gig.validation.translation_request_already_open_for_language')],
                ]);
            }
        }

        $oldValues = [
            'language' => $gig->language,
            'proposed_price' => $gig->proposed_price,
            'currency' => $gig->currency,
            'deadline' => $gig->deadline?->toISOString(),
            'special_requirements' => $gig->special_requirements,
            'status' => $gig->status?->value,
        ];

        if ($data->targetLanguageProvided) {
            $gig->language = $data->targetLanguage;
        }
        if ($data->proposedPriceProvided) {
            $gig->proposed_price = $data->proposedPrice;
        }
        if ($data->currencyProvided) {
            $gig->currency = $data->currency;
        }
        if ($data->deadlineProvided) {
            $gig->deadline = $data->deadline;
        }
        if ($data->specialRequirementsProvided) {
            $gig->special_requirements = $data->specialRequirements;
        }
        if ($data->status !== null) {
            $gig->status = $data->status;
        }

        $gig->updated_by_user_id = Auth::id();
        $gig->save();

        audit()->log(
            event: 'gig.updated',
            userId: Auth::id(),
            auditable: $gig,
            oldValues: $oldValues,
            newValues: [
                'language' => $gig->language,
                'proposed_price' => $gig->proposed_price,
                'currency' => $gig->currency,
                'deadline' => $gig->deadline?->toISOString(),
                'special_requirements' => $gig->special_requirements,
                'status' => $gig->status?->value,
            ],
            metadata: [
                'source' => 'poem_translation_gig_update',
                'poem_id' => $gig->giggable_id,
            ],
        );

        return $gig->fresh();
    }
}
