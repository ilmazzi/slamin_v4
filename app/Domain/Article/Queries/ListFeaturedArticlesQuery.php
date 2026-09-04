<?php

namespace Domain\Article\Queries;

use Domain\Article\DTOs\ListFeaturedArticlesData;
use Domain\Article\Models\Article;
use Domain\Article\Enums\ArticleStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListFeaturedArticlesQuery
{
    public function execute(ListFeaturedArticlesData $data): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->where('is_featured', true)
            ->orderBy('featured_rank');

        if ($data->articleCategoryId) {
            $query->where('article_category_id', $data->articleCategoryId);
        }

        return $query->paginate($data->perPage);
    }
}