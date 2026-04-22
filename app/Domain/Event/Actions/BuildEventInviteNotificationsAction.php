<?php

namespace Domain\Event\Actions;

use Domain\Event\DTOs\StoreEventData;
use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Domain\Event\Models\EventParticipantInvite;
use Illuminate\Support\Collection;

class BuildEventInviteNotificationsAction
{
    /**
     * @param array<int> $previousParticipantUserIds
     * @param array<int> $previousAudienceUserIds
     * @param array<int, string> $previousExternalParticipantKeys
     * @return array{participantInvites: Collection<int, EventParticipantInvite>, externalParticipantInvites: Collection<int, EventParticipantInvite>, audienceInvites: Collection<int, EventAudienceInvite>}
     */
    public function execute(
        Event $event,
        StoreEventData $data,
        array $previousParticipantUserIds = [],
        array $previousAudienceUserIds = [],
        array $previousExternalParticipantKeys = [],
    ): array {
        $participantInvites = $event->participantInvites()
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', $previousParticipantUserIds)
            ->get();

        $externalParticipantInvites = $event->participantInvites()
            ->where('invite_type', 'external')
            ->whereNotNull('email')
            ->get()
            ->filter(function (EventParticipantInvite $invite) use ($previousExternalParticipantKeys): bool {
                $key = strtolower((string) $invite->email) . '|' . strtolower((string) $invite->role);

                return ! in_array($key, $previousExternalParticipantKeys, true);
            })
            ->values();

        $audienceInvites = $event->audienceInvites()
            ->whereNotIn('user_id', $previousAudienceUserIds)
            ->get();

        return [
            'participantInvites' => $participantInvites,
            'externalParticipantInvites' => $externalParticipantInvites,
            'audienceInvites' => $audienceInvites,
        ];
    }
}
