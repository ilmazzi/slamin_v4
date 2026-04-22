<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;

class DeletePoemTranslationGigAction
{
    public function execute(Gig $gig): void
    {
        audit()->log(
            event: 'gig.deleted',
            userId: Auth::id(),
            auditable: $gig,
            oldValues: [
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
                'type' => $gig->type?->value,
                'language' => $gig->language,
                'proposed_price' => $gig->proposed_price,
                'status' => $gig->status?->value,
            ],
            metadata: [
                'source' => 'poem_translation_gig_delete',
                'poem_id' => $gig->giggable_id,
            ],
        );

        $gig->delete();
    }
}
