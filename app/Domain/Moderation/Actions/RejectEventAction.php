<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class RejectEventAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(Event $event, int $moderatorUserId, string $note): Event
    {
        if ($event->status !== EventStatus::PendingReview) {
            throw ValidationException::withMessages([
                'event' => [__('moderation.errors.event_reject_pending_only')],
            ]);
        }

        $event->update([
            'status' => EventStatus::Rejected,
            'published_at' => null,
        ]);

        $event->moderationDecisions()->create([
            'moderator_user_id' => $moderatorUserId,
            'decision' => ModerationDecisionType::Rejected,
            'note' => $note,
        ]);

        audit()->log(
            event: 'event.rejected',
            userId: $moderatorUserId,
            auditable: $event,
            newValues: [
                'status' => $event->status?->value,
                'published_at' => $event->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'event_moderation_reject',
                'note' => $note,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $event->user_id,
            type: 'event_rejected',
            title: __('moderation.notifications.event_rejected_title'),
            message: __('moderation.notifications.event_rejected_message', ['title' => $event->title]),
            data: [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'status' => $event->status?->value,
                'moderation_note' => $note,
            ],
        );

        return $event->fresh([
            'user.profile',
            'category.translations',
            'festival',
            'occurrences',
            'venueDetail',
            'onlineDetail',
        ]);
    }
}
