<?php

namespace Domain\Article\Actions;

use Domain\Article\Enums\ArticleStatus;
use Domain\Article\Models\Article;

class ShowArticleAction
{
    public function execute(string $slug, ?int $authUserId = null): Article
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('slug', $slug)
            ->where(function ($query) use ($authUserId) {
                if ($authUserId) {
                    $query->where('user_id', $authUserId)
                        ->orWhere(function ($query) {
                            $query->where('status', ArticleStatus::Published)
                                ->where('is_public', true);
                        });

                    return;
                }

                $query->where('status', ArticleStatus::Published)
                    ->where('is_public', true);
            });

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        return $query->firstOrFail();
    }
}
