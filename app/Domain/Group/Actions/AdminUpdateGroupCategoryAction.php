<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\GroupCategory;
use Illuminate\Support\Facades\DB;

class AdminUpdateGroupCategoryAction
{
    public function execute(GroupCategory $category, array $data, int $userId): GroupCategory
    {
        return DB::transaction(function () use ($category, $data, $userId) {
            $oldValues = [
                'slug' => $category->slug,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ];

            $category->update([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $locales = collect($data['translations'])->pluck('locale')->all();
            $category->translations()->whereNotIn('locale', $locales)->delete();

            foreach ($data['translations'] as $translation) {
                $category->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    ['name' => $translation['name']]
                );
            }

            audit()->log(
                event: 'group_category.updated',
                userId: $userId,
                auditable: $category,
                oldValues: $oldValues,
                newValues: [
                    'slug' => $category->slug,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_group_category_update',
                ],
            );

            return $category->load('translations');
        });
    }
}
