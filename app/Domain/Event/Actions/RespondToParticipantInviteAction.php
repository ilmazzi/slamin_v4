<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventParticipantInvite;
use Domain\Notification\Actions\CreateNotificationAction;

class RespondToParticipantInviteAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(EventParticipantInvite $invite, int $userId, string $status): EventParticipantInvite
    {
        $oldValues = [
            'status' => $invite->status,
        ];

        $invite->update([
            'status' => $status,
        ]);

        audit()->log(
            event: 'event_participant_invite.responded',
            userId: $userId,
            auditable: $invite->event,
            oldValues: $oldValues,
            newValues: [
                'invite_id' => $invite->id,
                'status' => $invite->status,
            ],
            metadata: [
                'source' => 'event_participant_invite_response',
            ],
        );

        $this->createNotificationAction->execute(
            userId: $invite->event->user_id,
            type: 'event_participant_invite_response',
            title: 'Risposta a invito partecipante',
            message: 'Un invitato ha ' . ($status === 'accepted' ? 'accettato' : 'rifiutato') . ' l\'invito all\'evento "' . $invite->event->title . '".',
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
