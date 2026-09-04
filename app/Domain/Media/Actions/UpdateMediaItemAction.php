<?php

namespace Domain\Media\Actions;

use Domain\Media\DTOs\UpdateMediaItemData;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;

class UpdateMediaItemAction
{
    public function execute(MediaItem $media, UpdateMediaItemData $data, int $userId): MediaItem
    {
        $updates = [];

        if ($data->titleProvided) {
            $updates['title'] = $data->title;
        }

        if ($data->descriptionProvided) {
            $updates['description'] = $data->description;
        }

        if ($data->visibilityProvided && $data->visibility !== null) {
            $updates['visibility'] = $data->visibility;
            $updates['published_at'] = $data->visibility === MediaVisibility::Public ? now() : null;
        }

        if ($updates !== []) {
            $media->update($updates);
        }

        audit()->log(
            event: 'media.updated',
            userId: $userId,
            auditable: $media,
            newValues: $updates,
            metadata: [
                'source' => 'media_update',
            ],
        );

        return $media->fresh()->load('user.profile');
    }
}
