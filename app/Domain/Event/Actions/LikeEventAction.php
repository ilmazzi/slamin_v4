<?php

namespace Domain\Event\Actions;

use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Engagement\Models\Like;
use Domain\Event\Models\Event;

class LikeEventAction
{
    public function __construct(
        private readonly NotifyContentLikedAction $notifyContentLikedAction,
    ) {}

    public function execute(Event $event, int $userId): void
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $event->getMorphClass(),
            'likeable_id' => $event->id,
        ]);

        $this->notifyContentLikedAction->execute($like, $event);

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
