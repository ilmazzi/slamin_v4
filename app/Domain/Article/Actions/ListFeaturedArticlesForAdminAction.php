<?php

namespace Domain\Article\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Illuminate\Support\Collection;

class ListFeaturedArticlesForAdminAction
{
    public function execute(): Collection
    {
        return Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks', 'comments'])
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->where('is_featured', true)
            ->orderBy('featured_rank')
            ->orderBy('id')
            ->get();
    }
}
