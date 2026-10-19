<?php

namespace Domain\Article\Queries;

use Domain\Article\DTOs\ListAdminArticlesData;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminArticlesQuery
{
    public function execute(ListAdminArticlesData $data): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks', 'comments'])
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->orderByDesc('is_featured')
            ->orderBy('featured_rank')
            ->orderByDesc('published_at');

        if ($data->search) {
            $term = '%'.mb_strtolower($data->search).'%';
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$term]);
            });
        }

        return $query->paginate($data->perPage);
    }
}
