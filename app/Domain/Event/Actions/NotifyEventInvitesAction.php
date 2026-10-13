<?php

namespace Domain\Event\Actions;

use App\Mail\EventExternalAudienceInviteMail;
use App\Mail\EventExternalParticipantInviteMail;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventParticipantInvite;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotifyEventInvitesAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    /**
     * @param  Collection<int, EventParticipantInvite>  $participantInvites
     * @param  Collection<int, EventParticipantInvite>  $externalParticipantInvites
     * @param  Collection<int, EventAudienceInvite>  $audienceInvites
     * @param  Collection<int, EventAudienceInvite>  $externalAudienceInvites
     */
    public function execute(
        Event $event,
        Collection $participantInvites,
        Collection $externalParticipantInvites,
        Collection $audienceInvites,
        Collection $externalAudienceInvites,
    ): void {
        foreach ($participantInvites as $invite) {
            if (! $invite->user_id) {
                continue;
            }

            $this->createNotificationAction->execute(
                userId: $invite->user_id,
                type: 'event_participant_invited',
                title: __('event.notifications.participant_invited_title'),
                message: __('event.notifications.participant_invited_message', [
                    'role' => $invite->role,
                    'title' => $event->title,
                ]),
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'invite_id' => $invite->id,
                    'role' => $invite->role,
                    'status' => $invite->status,
                ],
            );
        }

        foreach ($externalParticipantInvites as $invite) {
            if (! $invite->email) {
                continue;
            }

            Mail::to($invite->email)->send(new EventExternalParticipantInviteMail(
                event: $event->loadMissing(['occurrences', 'venueDetail', 'onlineDetail']),
                invite: $invite,
            ));

            if ($invite->status !== 'sent') {
                $invite->update([
                    'status' => 'sent',
                ]);
            }
        }

        foreach ($audienceInvites as $invite) {
            $this->createNotificationAction->execute(
                userId: $invite->user_id,
                type: 'event_audience_invited',
                title: __('event.notifications.audience_invited_title'),
                message: __('event.notifications.audience_invited_message', [
                    'title' => $event->title,
                ]),
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                    'invite_id' => $invite->id,
                    'status' => $invite->status,
                ],
            );
        }

        foreach ($externalAudienceInvites as $invite) {
            if (! $invite->email) {
                continue;
            }

            Mail::to($invite->email)->send(new EventExternalAudienceInviteMail(
                event: $event->loadMissing(['occurrences', 'venueDetail', 'onlineDetail']),
                invite: $invite,
            ));

            if ($invite->status !== 'sent') {
                $invite->update(['status' => 'sent']);
            }
        }
    }
}
