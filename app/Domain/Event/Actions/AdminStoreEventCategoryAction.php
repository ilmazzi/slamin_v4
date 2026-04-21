<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventCategory;
use Illuminate\Support\Facades\DB;

class AdminStoreEventCategoryAction
{
    public function execute(array $data, int $userId): EventCategory
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = EventCategory::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $category->translations()->createMany($data['translations']);

            audit()->log(
                event: 'event_category.created',
                userId: $userId,
                auditable: $category,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_event_category_store',
                ],
            );

            return $category->load('translations');
        });
    }
}
