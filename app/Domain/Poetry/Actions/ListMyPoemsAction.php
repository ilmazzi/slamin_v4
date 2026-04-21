<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyPoemsAction
{
    public function execute(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $userId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $userId),
            ])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
