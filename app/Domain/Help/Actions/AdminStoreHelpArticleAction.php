<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;
use Illuminate\Support\Facades\DB;

class AdminStoreHelpArticleAction
{
    public function execute(array $data, int $userId): HelpArticle
    {
        return DB::transaction(function () use ($data, $userId) {
            $article = HelpArticle::query()->create([
                'help_category_id' => $data['help_category_id'],
                'slug' => $data['slug'],
                'is_published' => $data['is_published'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $article->translations()->createMany($data['translations']);

            audit()->log(
                event: 'help_article.created',
                userId: $userId,
                auditable: $article,
                newValues: [
                    'help_category_id' => $article->help_category_id,
                    'slug' => $article->slug,
                    'is_published' => $article->is_published,
                    'sort_order' => $article->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_help_article_store',
                ],
            );

            return $article->load(['translations', 'category.translations']);
        });
    }
}
