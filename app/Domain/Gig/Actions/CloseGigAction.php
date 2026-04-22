<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CloseGigAction
{
    public function execute(Gig $gig): Gig
    {
        if ($gig->filled_quantity < $gig->quantity) {
            throw ValidationException::withMessages([
                'gig' => [__('gig.validation.cannot_close_until_filled')],
            ]);
        }

        $oldStatus = $gig->status;

        $gig->status = GigStatus::Closed;
        $gig->closed_at = now();
        $gig->updated_by_user_id = Auth::id();
        $gig->save();

        audit()->log(
            event: 'gig.closed',
            userId: Auth::id(),
            auditable: $gig,
            oldValues: [
                'status' => $oldStatus?->value,
            ],
            newValues: [
                'status' => $gig->status?->value,
                'closed_at' => $gig->closed_at?->toISOString(),
            ],
            metadata: [
                'source' => 'gig_close',
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
            ],
        );

        return $gig->fresh();
    }
}
