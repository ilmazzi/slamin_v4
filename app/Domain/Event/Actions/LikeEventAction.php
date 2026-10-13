<?php

namespace Domain\Event\Actions;

use Domain\Engagement\Models\Like;
use Domain\Event\Models\Event;

class LikeEventAction
{
    public function execute(Event $event, int $userId): void
    {
        Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $event->getMorphClass(),
            'likeable_id' => $event->id,
        ]);

        audit()->log(
            event: 'event.liked',
            userId: $userId,
            auditable: $event,
            metadata: [
                'source' => 'event_like',
            ],
        );
    }
}
