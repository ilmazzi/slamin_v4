<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\ArticleCategory;

class AdminDeleteArticleCategoryAction
{
    public function execute(ArticleCategory $category, int $userId): bool
    {
        if ($category->articles()->exists()) {
            return false;
        }

        $oldValues = [
            'slug' => $category->slug,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'translations' => $category->translations()
                ->orderBy('locale')
                ->get(['locale', 'name'])
                ->map(fn ($translation) => [
                    'locale' => $translation->locale,
                    'name' => $translation->name,
                ])->values()->all(),
        ];

        $category->delete();

        audit()->log(
            event: 'article_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_article_category_delete',
            ],
        );

        return true;
    }
}
