<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\DTOs\StoreGigData;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Support\Facades\Auth;

class StoreEventGigAction
{
    public function execute(Event $event, StoreGigData $data): Gig
    {
        $now = now();

        $gig = $event->gigs()->create([
            'type' => $data->type,
            'quantity' => $data->quantity,
            'filled_quantity' => 0,
            'language' => $data->language,
            'hospitality_required' => $data->hospitalityRequired,
            'description' => $data->description,
            'status' => $data->status,
            'opened_at' => $data->status === GigStatus::Open ? $now : null,
            'closed_at' => in_array($data->status, [GigStatus::Closed, GigStatus::Cancelled], true) ? $now : null,
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
                'quantity' => $gig->quantity,
                'language' => $gig->language,
                'hospitality_required' => $gig->hospitality_required,
                'description' => $gig->description,
                'status' => $gig->status?->value,
            ],
            metadata: [
                'source' => 'event_gig_store',
                'event_id' => $event->id,
            ],
        );

        return $gig->fresh();
    }
}
