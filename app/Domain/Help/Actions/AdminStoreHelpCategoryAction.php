<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpCategory;
use Illuminate\Support\Facades\DB;

class AdminStoreHelpCategoryAction
{
    public function execute(array $data, int $userId): HelpCategory
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = HelpCategory::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $category->translations()->createMany($data['translations']);

            audit()->log(
                event: 'help_category.created',
                userId: $userId,
                auditable: $category,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_help_category_store',
                ],
            );

            return $category->load('translations')->loadCount('articles');
        });
    }
}
