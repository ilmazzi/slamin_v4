<?php

namespace Domain\Media\Actions;

use Domain\Engagement\Models\Like;
use Domain\Media\Models\MediaItem;

class UnlikeMediaItemAction
{
    public function execute(MediaItem $media, int $userId): void
    {
        Like::query()
            ->where('user_id', $userId)
            ->where('likeable_type', $media->getMorphClass())
            ->where('likeable_id', $media->id)
            ->delete();

        audit()->log(
            event: 'media.unliked',
            userId: $userId,
            auditable: $media,
            metadata: [
                'source' => 'media_unlike',
            ],
        );
    }
}
