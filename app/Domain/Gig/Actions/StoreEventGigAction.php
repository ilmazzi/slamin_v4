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
            'proposed_price' => $data->cachetEnabled ? $data->proposedPrice : null,
            'currency' => ($data->cachetEnabled || $data->travelExpensesEnabled) ? $data->currency : null,
            'deadline' => $data->deadline,
            'special_requirements' => $data->specialRequirements,
            'cachet_enabled' => $data->cachetEnabled,
            'travel_expenses_enabled' => $data->travelExpensesEnabled,
            'travel_expenses_max' => $data->travelExpensesEnabled ? $data->travelExpensesMax : null,
            'accommodation_enabled' => $data->accommodationEnabled,
            'accommodation_details' => $data->accommodationEnabled ? $data->accommodationDetails : null,
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
                'proposed_price' => $gig->proposed_price,
                'currency' => $gig->currency,
                'deadline' => $gig->deadline?->toISOString(),
                'special_requirements' => $gig->special_requirements,
                'cachet_enabled' => $gig->cachet_enabled,
                'travel_expenses_enabled' => $gig->travel_expenses_enabled,
                'travel_expenses_max' => $gig->travel_expenses_max,
                'accommodation_enabled' => $gig->accommodation_enabled,
                'accommodation_details' => $gig->accommodation_details,
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
