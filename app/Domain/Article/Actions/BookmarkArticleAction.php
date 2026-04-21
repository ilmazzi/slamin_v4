<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Models\Bookmark;

class BookmarkArticleAction
{
    public function execute(Article $article, int $userId): void
    {
        Bookmark::query()->firstOrCreate([
            'user_id' => $userId,
            'bookmarkable_type' => $article->getMorphClass(),
            'bookmarkable_id' => $article->id,
        ]);

        audit()->log(
            event: 'article.bookmarked',
            userId: $userId,
            auditable: $article,
            metadata: [
                'source' => 'article_bookmark',
            ],
        );
    }
}
