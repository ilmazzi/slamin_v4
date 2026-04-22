<?php

namespace Domain\Media\Actions;

use Domain\Engagement\Models\Like;
use Domain\Media\Models\MediaItem;

class LikeMediaItemAction
{
    public function execute(MediaItem $media, int $userId): void
    {
        Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $media->getMorphClass(),
            'likeable_id' => $media->id,
        ]);

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
