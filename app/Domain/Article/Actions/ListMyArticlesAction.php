<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyArticlesAction
{
    public function execute(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Article::query()
            ->with(['user.profile', 'category.translations'])
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
