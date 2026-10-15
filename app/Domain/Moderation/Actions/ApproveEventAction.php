<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Moderation\Enums\ModerationDecisionType;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Validation\ValidationException;

class ApproveEventAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Event $event, int $moderatorUserId, ?string $note = null): Event
    {
        if ($event->status !== EventStatus::PendingReview) {
            throw ValidationException::withMessages([
                'event' => [__('moderation.errors.event_approve_pending_only')],
            ]);
        }

        $event->update([
            'status' => EventStatus::Published,
            'published_at' => $event->published_at ?? now(),
        ]);

        $event->moderationDecisions()->create([
            'moderator_user_id' => $moderatorUserId,
            'decision' => ModerationDecisionType::Approved,
            'note' => $note,
        ]);

        audit()->log(
            event: 'event.approved',
            userId: $moderatorUserId,
            auditable: $event,
            newValues: [
                'status' => $event->status?->value,
                'published_at' => $event->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'event_moderation_approve',
                'note' => $note,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $event->user_id,
            type: 'event_approved',
            title: fn () => __('moderation.notifications.event_approved_title'),
            message: fn () => __('moderation.notifications.event_approved_message', ['title' => $event->title]),
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
