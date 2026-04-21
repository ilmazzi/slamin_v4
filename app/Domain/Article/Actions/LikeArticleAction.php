<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Models\Like;

class LikeArticleAction
{
    public function execute(Article $article, int $userId): void
    {
        Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $article->getMorphClass(),
            'likeable_id' => $article->id,
        ]);

        audit()->log(
            event: 'article.liked',
            userId: $userId,
            auditable: $article,
            metadata: [
                'source' => 'article_like',
            ],
        );
    }
}
