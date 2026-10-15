<?php

namespace Domain\Media\Actions;

use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Engagement\Models\Like;
use Domain\Media\Models\MediaItem;

class LikeMediaItemAction
{
    public function __construct(
        private readonly NotifyContentLikedAction $notifyContentLikedAction,
    ) {}

    public function execute(MediaItem $media, int $userId): void
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $media->getMorphClass(),
            'likeable_id' => $media->id,
        ]);

        $this->notifyContentLikedAction->execute($like, $media);

        audit()->log(
            event: 'media.liked',
            userId: $userId,
            auditable: $media,
            metadata: [
                'source' => 'media_like',
            ],
        );
    }
}
