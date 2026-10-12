<?php

namespace Domain\Event\Actions;

use Domain\Engagement\Models\Like;
use Domain\Event\Models\Event;

class UnlikeEventAction
{
    public function execute(Event $event, int $userId): void
    {
        Like::query()
            ->where('user_id', $userId)
            ->where('likeable_type', $event->getMorphClass())
            ->where('likeable_id', $event->id)
            ->delete();

        audit()->log(
            event: 'event.unliked',
            userId: $userId,
            auditable: $event,
            metadata: [
                'source' => 'event_unlike',
            ],
        );
    }
}
