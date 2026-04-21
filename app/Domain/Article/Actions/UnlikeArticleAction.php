<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Models\Like;

class UnlikeArticleAction
{
    public function execute(Article $article, int $userId): void
    {
        Like::query()
            ->where('user_id', $userId)
            ->where('likeable_type', $article->getMorphClass())
            ->where('likeable_id', $article->id)
            ->delete();

        audit()->log(
            event: 'article.unliked',
            userId: $userId,
            auditable: $article,
            metadata: [
                'source' => 'article_unlike',
            ],
        );
    }
}
