<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\GroupCategory;
use Illuminate\Support\Facades\DB;

class AdminStoreGroupCategoryAction
{
    public function execute(array $data, int $userId): GroupCategory
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = GroupCategory::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $category->translations()->createMany($data['translations']);

            audit()->log(
                event: 'group_category.created',
                userId: $userId,
                auditable: $category,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_group_category_store',
                ],
            );

            return $category->load('translations');
        });
    }
}
