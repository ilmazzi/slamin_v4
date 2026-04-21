<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Models\Bookmark;

class RemoveBookmarkArticleAction
{
    public function execute(Article $article, int $userId): void
    {
        Bookmark::query()
            ->where('user_id', $userId)
            ->where('bookmarkable_type', $article->getMorphClass())
            ->where('bookmarkable_id', $article->id)
            ->delete();

        audit()->log(
            event: 'article.unbookmarked',
            userId: $userId,
            auditable: $article,
            metadata: [
                'source' => 'article_unbookmark',
            ],
        );
    }
}
