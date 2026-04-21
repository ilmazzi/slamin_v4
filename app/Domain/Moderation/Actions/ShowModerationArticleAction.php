<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Models\Article;

class ShowModerationArticleAction
{
    public function execute(Article $article): Article
    {
        return $article->load([
            'user.profile',
            'category.translations',
            'moderationDecisions.moderator.profile',
        ])->loadCount(['likes', 'bookmarks']);
    }
}
