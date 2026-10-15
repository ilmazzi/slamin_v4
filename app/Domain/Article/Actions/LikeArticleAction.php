<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;
use Domain\Engagement\Actions\NotifyContentLikedAction;
use Domain\Engagement\Models\Like;

class LikeArticleAction
{
    public function __construct(
        private readonly NotifyContentLikedAction $notifyContentLikedAction,
    ) {}

    public function execute(Article $article, int $userId): void
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $userId,
            'likeable_type' => $article->getMorphClass(),
            'likeable_id' => $article->id,
        ]);

        $this->notifyContentLikedAction->execute($like, $article);

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
