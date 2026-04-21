<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventCategory;

class AdminDeleteEventCategoryAction
{
    public function execute(EventCategory $category, int $userId): bool
    {
        if ($category->events()->exists()) {
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
            event: 'event_category.deleted',
            userId: $userId,
            auditable: $category,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_event_category_delete',
            ],
        );

        return true;
    }
}
