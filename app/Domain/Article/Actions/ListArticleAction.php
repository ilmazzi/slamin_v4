<?php

namespace Domain\Article\Actions;

use Domain\Article\DTOs\ListArticleData;
use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Shared\Support\AuthorNicknameFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListArticleAction
{
    public function execute(ListArticleData $data, ?int $authUserId = null): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('status', ArticleStatus::Published)
            ->where('is_public', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        AuthorNicknameFilter::apply($query, $data->nickname);

        if ($data->articleCategoryId) {
            $query->where('article_category_id', $data->articleCategoryId);
        }

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        return $query->paginate($data->perPage);
    }
}
