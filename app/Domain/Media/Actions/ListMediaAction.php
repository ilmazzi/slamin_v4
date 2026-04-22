<?php

namespace Domain\Media\Actions;

use Domain\Media\Enums\MediaStatus;
use Domain\Media\Enums\MediaVisibility;
use Domain\Media\Models\MediaItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMediaAction
{
    public function execute(?int $viewerUserId = null, int $perPage = 15): LengthAwarePaginator
    {
        return MediaItem::query()
            ->with(['user.profile'])
            ->withCount(['likes', 'comments', 'snaps'])
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $viewerUserId ?? 0),
            ])
            ->where('status', MediaStatus::Ready)
            ->where('visibility', MediaVisibility::Public)
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage);
    }
}
