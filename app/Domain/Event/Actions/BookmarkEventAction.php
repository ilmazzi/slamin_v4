<?php

namespace Domain\Event\Actions;

use Domain\Engagement\Models\Bookmark;
use Domain\Event\Models\Event;

class BookmarkEventAction
{
    public function execute(Event $event, int $userId): void
    {
        Bookmark::query()->firstOrCreate([
            'user_id' => $userId,
            'bookmarkable_type' => $event->getMorphClass(),
            'bookmarkable_id' => $event->id,
        ]);

        audit()->log(
            event: 'event.bookmarked',
            userId: $userId,
            auditable: $event,
            metadata: [
                'source' => 'event_bookmark',
            ],
        );
    }
}
