<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StoreGigNegotiationEventData;
use Domain\Gig\Enums\GigNegotiationEventType;
use Domain\Gig\Models\GigNegotiation;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;

class StoreGigNegotiationEventAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(GigNegotiation $negotiation, StoreGigNegotiationEventData $data): \Domain\Gig\Models\GigNegotiationEvent
    {
        $event = $negotiation->events()->create([
            'author_user_id' => Auth::id(),
            'event_type' => $data->eventType,
            'price' => $data->price,
            'currency' => $data->currency,
            'deadline' => $data->deadline,
            'note' => $data->note,
        ]);

        audit()->log(
            event: 'gig.negotiation_event_created',
            userId: Auth::id(),
            auditable: $event,
            newValues: [
                'gig_negotiation_id' => $negotiation->id,
                'event_type' => $event->event_type?->value,
                'price' => $event->price,
                'currency' => $event->currency,
                'note' => $event->note,
            ],
            metadata: [
                'source' => 'gig_negotiation_event_store',
            ],
        );

        $targetUserId = Auth::id() === $negotiation->owner_user_id
            ? $negotiation->translator_user_id
            : $negotiation->owner_user_id;

        $this->createNotificationAction->execute(
            userId: $targetUserId,
            type: 'gig.negotiation_event_created',
            title: __('gig.notifications.negotiation_updated_title'),
            message: __('gig.notifications.negotiation_updated_message'),
            data: [
                'gig_id' => $negotiation->gig_id,
                'negotiation_id' => $negotiation->id,
                'event_type' => $event->event_type->value,
            ],
        );

        return $event->fresh('author.profile');
    }
}
