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
     * @return array{participantInvites: Collection<int, EventParticipantInvite>, audienceInvites: Collection<int, EventAudienceInvite>}
     */
    public function execute(
        Event $event,
        StoreEventData $data,
        array $previousParticipantUserIds = [],
        array $previousAudienceUserIds = [],
    ): array {
        $participantInvites = $event->participantInvites()
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', $previousParticipantUserIds)
            ->get();

        $audienceInvites = $event->audienceInvites()
            ->whereNotIn('user_id', $previousAudienceUserIds)
            ->get();

        return [
            'participantInvites' => $participantInvites,
            'audienceInvites' => $audienceInvites,
        ];
    }
}
