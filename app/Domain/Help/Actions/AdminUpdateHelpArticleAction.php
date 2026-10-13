<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;
use Illuminate\Support\Facades\DB;

class AdminUpdateHelpArticleAction
{
    public function execute(HelpArticle $article, array $data, int $userId): HelpArticle
    {
        return DB::transaction(function () use ($article, $data, $userId) {
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

            $article->update([
                'help_category_id' => $data['help_category_id'],
                'slug' => $data['slug'],
                'is_published' => $data['is_published'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $locales = collect($data['translations'])->pluck('locale')->all();
            $article->translations()->whereNotIn('locale', $locales)->delete();

            foreach ($data['translations'] as $translation) {
                $article->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    [
                        'title' => $translation['title'],
                        'body' => $translation['body'],
                    ],
                );
            }

            audit()->log(
                event: 'help_article.updated',
                userId: $userId,
                auditable: $article,
                oldValues: $oldValues,
                newValues: [
                    'help_category_id' => $article->help_category_id,
                    'slug' => $article->slug,
                    'is_published' => $article->is_published,
                    'sort_order' => $article->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_help_article_update',
                ],
            );

            return $article->load(['translations', 'category.translations']);
        });
    }
}
