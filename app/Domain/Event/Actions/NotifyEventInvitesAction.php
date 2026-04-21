<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Collection;

class NotifyEventInvitesAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    /**
     * @param Collection<int, EventParticipantInvite> $participantInvites
     * @param Collection<int, EventAudienceInvite> $audienceInvites
     */
    public function execute(Event $event, Collection $participantInvites, Collection $audienceInvites): void
    {
        foreach ($participantInvites as $invite) {
            if (! $invite->user_id) {
                continue;
            }

            $this->createNotificationAction->execute(
                userId: $invite->user_id,
                type: 'event_participant_invited',
                title: 'Invito a partecipare a un evento',
                message: 'Sei stato invitato come ' . $invite->role . ' all\'evento "' . $event->title . '".',
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'invite_id' => $invite->id,
                    'role' => $invite->role,
                    'status' => $invite->status,
                ],
            );
        }

        foreach ($audienceInvites as $invite) {
            $this->createNotificationAction->execute(
                userId: $invite->user_id,
                type: 'event_audience_invited',
                title: 'Invito a partecipare come pubblico',
                message: 'Sei stato invitato a partecipare come pubblico all\'evento "' . $event->title . '".',
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'invite_id' => $invite->id,
                    'status' => $invite->status,
                ],
            );
        }
    }
}
