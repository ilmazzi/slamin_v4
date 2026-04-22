<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;

class DeleteEventGigAction
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
                'quantity' => $gig->quantity,
                'language' => $gig->language,
                'status' => $gig->status?->value,
            ],
            metadata: [
                'source' => 'event_gig_delete',
                'event_id' => $gig->giggable_id,
            ],
        );

        $gig->delete();
    }
}
