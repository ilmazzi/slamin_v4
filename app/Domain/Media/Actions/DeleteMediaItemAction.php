<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaType;
use Domain\Media\Models\MediaItem;
use Illuminate\Support\Facades\Storage;

class DeleteMediaItemAction
{
    public function execute(MediaItem $media, int $userId): void
    {
        if ($media->type === MediaType::Photo && $media->file_path) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        audit()->log(
            event: 'media.deleted',
            userId: $userId,
            auditable: $media,
            metadata: [
                'source' => 'media_delete',
            ],
        );
    }
}
