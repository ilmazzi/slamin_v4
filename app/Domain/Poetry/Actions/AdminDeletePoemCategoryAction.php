<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemCategory;

class AdminDeletePoemCategoryAction
{
    public function execute(PoemCategory $category, int $userId): bool
    {
        if ($category->poems()->exists()) {
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
            event: 'poem_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_poem_category_delete',
            ],
        );

        return true;
    }
}
