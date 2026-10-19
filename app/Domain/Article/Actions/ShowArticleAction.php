<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;

class ShowArticleAction
{
    public function execute(string $slug, ?int $authUserId = null): Article
    {
        $query = Article::query()
            ->with(['user.profile', 'category.translations', 'groupLinks.group.category.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('slug', $slug)
            ->visibleTo($authUserId);

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        return $query->firstOrFail();
    }
}
