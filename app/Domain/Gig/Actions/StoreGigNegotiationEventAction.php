<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StoreGigNegotiationEventData;
use Domain\Gig\Enums\GigNegotiationEventType;
use Domain\Gig\Enums\GigNegotiationStatus;
use Domain\Gig\Models\GigNegotiation;
use Domain\Gig\Models\GigNegotiationEvent;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreGigNegotiationEventAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(GigNegotiation $negotiation, StoreGigNegotiationEventData $data): GigNegotiationEvent
    {
        if ($negotiation->status !== GigNegotiationStatus::Open) {
            throw ValidationException::withMessages([
                'negotiation' => [__('gig.validation.negotiation_not_open')],
            ]);
        }

        $isOwner = (int) Auth::id() === (int) $negotiation->owner_user_id;
        $roleMismatch = ($data->eventType === GigNegotiationEventType::OwnerOffer && ! $isOwner)
            || ($data->eventType === GigNegotiationEventType::TranslatorOffer && $isOwner);

        if ($roleMismatch) {
            throw ValidationException::withMessages([
                'event_type' => [__('gig.validation.negotiation_event_not_allowed')],
            ]);
        }

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

        $targetUserId = (int) Auth::id() === (int) $negotiation->owner_user_id
            ? $negotiation->translator_user_id
            : $negotiation->owner_user_id;

        $this->createNotificationAction->execute(
            userId: $targetUserId,
            type: 'gig.negotiation_event_created',
            title: fn () => __('gig.notifications.negotiation_updated_title'),
            message: fn () => __('gig.notifications.negotiation_updated_message'),
            data: [
                'gig_id' => $negotiation->gig_id,
                'negotiation_id' => $negotiation->id,
                'event_type' => $event->event_type->value,
            ],
        );

        return $event->fresh('author.profile');
    }
}
