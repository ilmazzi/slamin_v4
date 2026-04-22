<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StorePoemTranslationGigData;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\GigType;
use Domain\Gig\Models\Gig;
use Domain\Poetry\Models\Poem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorePoemTranslationGigAction
{
    public function execute(Poem $poem, StorePoemTranslationGigData $data): Gig
    {
        $existingOpen = $poem->gigs()
            ->where('type', GigType::Translator->value)
            ->where('language', $data->targetLanguage)
            ->whereIn('status', [GigStatus::Draft->value, GigStatus::Open->value, GigStatus::Filled->value])
            ->exists();

        if ($existingOpen) {
            throw ValidationException::withMessages([
                'target_language' => [__('gig.validation.translation_request_already_open_for_language')],
            ]);
        }

        $now = now();

        $gig = $poem->gigs()->create([
            'type' => GigType::Translator,
            'quantity' => 1,
            'filled_quantity' => 0,
            'language' => $data->targetLanguage,
            'hospitality_required' => false,
            'description' => null,
            'proposed_price' => $data->proposedPrice,
            'currency' => $data->currency,
            'deadline' => $data->deadline,
            'special_requirements' => $data->specialRequirements,
            'status' => $data->status,
            'opened_at' => $data->status === GigStatus::Open ? $now : null,
            'created_by_user_id' => Auth::id(),
            'updated_by_user_id' => Auth::id(),
        ]);

        audit()->log(
            event: 'gig.created',
            userId: Auth::id(),
            auditable: $gig,
            newValues: [
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
                'type' => $gig->type?->value,
                'target_language' => $gig->language,
                'proposed_price' => $gig->proposed_price,
                'currency' => $gig->currency,
                'deadline' => $gig->deadline?->toISOString(),
                'special_requirements' => $gig->special_requirements,
                'status' => $gig->status?->value,
            ],
            metadata: [
                'source' => 'poem_translation_gig_store',
                'poem_id' => $poem->id,
            ],
        );

        return $gig->fresh();
    }
}
