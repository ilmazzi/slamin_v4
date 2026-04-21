<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;
use Domain\Moderation\DTOs\ListModerationArticlesData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListModerationArticlesAction
{
    public function execute(ListModerationArticlesData $data): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->orderByRaw(
                "case when status = ? then 0 when status = ? then 1 else 2 end",
                [ArticleStatus::PendingReview->value, ArticleStatus::Rejected->value]
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($data->status) {
            $query->where('status', $data->status);
        }

        if ($data->query) {
            $search = $data->query;
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('user.profile', fn ($q) => $q
                        ->where('nickname', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%"));
            });
        }

        if ($data->articleCategoryId) {
            $query->where('article_category_id', $data->articleCategoryId);
        }

        return $query->paginate($data->perPage);
    }
}
