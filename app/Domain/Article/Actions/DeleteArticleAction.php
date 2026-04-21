<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\Article;

class DeleteArticleAction
{
    public function execute(Article $article, int $userId): void
    {
        $article->delete();

        audit()->log(
            event: 'article.deleted',
            userId: $userId,
            auditable: $article,
            metadata: [
                'source' => 'article_delete',
            ],
        );
    }
}
