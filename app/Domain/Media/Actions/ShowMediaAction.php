<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;

class ShowMediaAction
{
    public function __construct(
        private readonly SyncPeerTubeVideoStatusAction $syncPeerTubeVideoStatusAction,
    ) {}

    public function execute(MediaItem $media, ?int $viewerUserId = null): MediaItem
    {
        if ($media->type === MediaType::Video && $media->status === MediaStatus::Processing) {
            try {
                $media = $this->syncPeerTubeVideoStatusAction->execute($media);
            } catch (\Throwable) {
                // Keep showing the original item if PeerTube is unreachable.
            }
        }

        return $media->loadCount(['likes', 'comments', 'snaps'])
            ->loadExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $viewerUserId ?? 0),
            ])
            ->load('user.profile');
    }
}
