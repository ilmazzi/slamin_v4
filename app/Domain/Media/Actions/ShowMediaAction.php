<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaItem;

class ShowMediaAction
{
    public function execute(MediaItem $media, ?int $viewerUserId = null): MediaItem
    {
        return $media->loadCount(['likes', 'comments', 'snaps'])
            ->loadExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $viewerUserId ?? 0),
            ])
            ->load('user.profile');
    }
}
