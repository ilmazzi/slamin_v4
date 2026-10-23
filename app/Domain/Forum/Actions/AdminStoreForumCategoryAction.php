<?php

namespace Domain\Forum\Actions;

use Domain\Forum\Models\ForumCategory;
use Illuminate\Support\Facades\DB;

class AdminStoreForumCategoryAction
{
    public function execute(array $data, int $userId): ForumCategory
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = ForumCategory::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $category->translations()->createMany($data['translations']);

            audit()->log(
                event: 'forum_category.created',
                userId: $userId,
                auditable: $category,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_forum_category_store',
                ],
            );

            return $category->load('translations');
        });
    }
}
