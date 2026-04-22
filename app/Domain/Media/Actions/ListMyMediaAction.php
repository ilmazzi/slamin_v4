<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyMediaAction
{
    public function execute(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return MediaItem::query()
            ->with(['user.profile'])
            ->withCount(['likes', 'comments', 'snaps'])
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate($perPage);
    }
}
