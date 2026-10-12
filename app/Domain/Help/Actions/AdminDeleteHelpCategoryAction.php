<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpCategory;

class AdminDeleteHelpCategoryAction
{
    public function execute(HelpCategory $category, int $userId): bool
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
            event: 'help_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_help_category_delete',
            ],
        );

        return true;
    }
}
