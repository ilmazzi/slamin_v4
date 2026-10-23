<?php

namespace Domain\Forum\Actions;

use Domain\Forum\Models\ForumCategory;

class AdminDeleteForumCategoryAction
{
    public function execute(ForumCategory $category, int $userId): bool
    {
        if ($category->posts()->exists()) {
            return false;
        }

        $oldValues = [
            'slug' => $category->slug,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'translations' => $category->translations()
                ->orderBy('locale')
                ->get(['locale', 'name', 'description'])
                ->map(fn ($translation) => [
                    'locale' => $translation->locale,
                    'name' => $translation->name,
                    'description' => $translation->description,
                ])->values()->all(),
        ];

        $category->delete();

        audit()->log(
            event: 'forum_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_forum_category_delete',
            ],
        );

        return true;
    }
}
