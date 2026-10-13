<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Notification\Actions\CreateNotificationAction;

class NotifyEventUpdatedAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    /**
     * @return list<int>
     */
    public function recipientUserIds(Event $event): array
    {
        $event->loadMissing([
            'bookmarks',
            'participantInvites',
            'audienceInvites',
            'gigs.applications',
        ]);

        $activeGigStatuses = [
            GigApplicationStatus::Pending,
            GigApplicationStatus::Accepted,
            GigApplicationStatus::Confirmed,
        ];

        $bookmarkIds = $event->bookmarks->pluck('user_id');
        $participantIds = $event->participantInvites
            ->filter(fn ($invite) => $invite->user_id && $invite->status !== 'declined')
            ->pluck('user_id');
        $audienceIds = $event->audienceInvites
            ->filter(fn ($invite) => $invite->user_id && $invite->status !== 'declined')
            ->pluck('user_id');
        $gigApplicantIds = $event->gigs
            ->flatMap(fn ($gig) => $gig->applications)
            ->filter(fn ($application) => in_array($application->status, $activeGigStatuses, true))
            ->pluck('user_id');

        return $bookmarkIds
            ->concat($participantIds)
            ->concat($audienceIds)
            ->concat($gigApplicantIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $event->user_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $userIds
     * @param  list<int>  $excludeUserIds
     */
    public function execute(Event $event, array $userIds, array $excludeUserIds = []): void
    {
        $excluded = array_values(array_unique(array_map(
            'intval',
            [...$excludeUserIds, (int) $event->user_id],
        )));

        foreach (array_unique($userIds) as $userId) {
            $id = (int) $userId;
            if ($id <= 0 || in_array($id, $excluded, true)) {
                continue;
            }

            $this->createNotificationAction->execute(
                userId: $id,
                type: 'event_updated',
                title: __('event.notifications.updated_title'),
                message: __('event.notifications.updated_message', [
                    'title' => $event->title,
                ]),
                data: [
                    'event_id' => $event->id,
                    'event_slug' => $event->slug,
                ],
            );
        }
    }
}
