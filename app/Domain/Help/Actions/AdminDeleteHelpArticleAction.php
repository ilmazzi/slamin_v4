<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;

class AdminDeleteHelpArticleAction
{
    public function execute(HelpArticle $article, int $userId): void
    {
        $oldValues = [
            'help_category_id' => $article->help_category_id,
            'slug' => $article->slug,
            'is_published' => $article->is_published,
            'sort_order' => $article->sort_order,
            'translations' => $article->translations()
                ->orderBy('locale')
                ->get(['locale', 'title', 'body'])
                ->map(fn ($translation) => [
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'body' => $translation->body,
                ])->values()->all(),
        ];

        $article->delete();

        audit()->log(
            event: 'help_article.deleted',
            userId: $userId,
            auditable: $article,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_help_article_delete',
            ],
        );
    }
}
