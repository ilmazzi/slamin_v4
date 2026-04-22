<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventAudienceInvite;
use Domain\Notification\Actions\CreateNotificationAction;

class RespondToAudienceInviteAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(EventAudienceInvite $invite, int $userId, string $status): EventAudienceInvite
    {
        $oldValues = [
            'status' => $invite->status,
        ];

        $invite->update([
            'status' => $status,
        ]);

        audit()->log(
            event: 'event_audience_invite.responded',
            userId: $userId,
            auditable: $invite->event,
            oldValues: $oldValues,
            newValues: [
                'invite_id' => $invite->id,
                'status' => $invite->status,
            ],
            metadata: [
                'source' => 'event_audience_invite_response',
            ],
        );

        $this->createNotificationAction->execute(
            userId: $invite->event->user_id,
            type: 'event_audience_invite_response',
            title: __('event.notifications.audience_response_title'),
            message: __('event.notifications.audience_response_message', [
                'response' => __('event.responses.' . ($status === 'accepted' ? 'accepted' : 'declined')),
                'title' => $invite->event->title,
            ]),
            data: [
                'event_id' => $invite->event->id,
                'event_slug' => $invite->event->slug,
                'invite_id' => $invite->id,
                'status' => $invite->status,
                'user_id' => $invite->user_id,
            ],
        );

        return $invite->fresh(['event', 'user.profile']);
    }
}
