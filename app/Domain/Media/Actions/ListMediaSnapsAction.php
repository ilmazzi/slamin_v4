<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaItem;
use Illuminate\Database\Eloquent\Collection;

class ListMediaSnapsAction
{
    public function execute(MediaItem $media): Collection
    {
        return $media->snaps()
            ->with(['user.profile'])
            ->get();
    }
}
