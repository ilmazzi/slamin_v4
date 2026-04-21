<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingReviewArticlesAction
{
    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        return Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('status', ArticleStatus::PendingReview)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
