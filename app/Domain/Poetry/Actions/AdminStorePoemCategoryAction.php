<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemCategory;
use Illuminate\Support\Facades\DB;

class AdminStorePoemCategoryAction
{
    public function execute(array $data, int $userId): PoemCategory
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = PoemCategory::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $category->translations()->createMany($data['translations']);

            audit()->log(
                event: 'poem_category.created',
                userId: $userId,
                auditable: $category,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_poem_category_store',
                ],
            );

            return $category->load('translations');
        });
    }
}
