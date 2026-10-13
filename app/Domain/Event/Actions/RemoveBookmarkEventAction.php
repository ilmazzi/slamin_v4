<?php

namespace Domain\Event\Actions;

use Domain\Engagement\Models\Bookmark;
use Domain\Event\Models\Event;

class RemoveBookmarkEventAction
{
    public function execute(Event $event, int $userId): void
    {
        Bookmark::query()
            ->where('user_id', $userId)
            ->where('bookmarkable_type', $event->getMorphClass())
            ->where('bookmarkable_id', $event->id)
            ->delete();

        audit()->log(
            event: 'event.unbookmarked',
            userId: $userId,
            auditable: $event,
            metadata: [
                'source' => 'event_unbookmark',
            ],
        );
    }
}
