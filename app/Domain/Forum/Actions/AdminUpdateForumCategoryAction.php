<?php

namespace Domain\Forum\Actions;

use Domain\Forum\Models\ForumCategory;
use Illuminate\Support\Facades\DB;

class AdminUpdateForumCategoryAction
{
    public function execute(ForumCategory $category, array $data, int $userId): ForumCategory
    {
        return DB::transaction(function () use ($category, $data, $userId) {
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
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description'] ?? null,
                    ]
                );
            }

            audit()->log(
                event: 'forum_category.updated',
                userId: $userId,
                auditable: $category,
                oldValues: $oldValues,
                newValues: [
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_forum_category_update',
                ],
            );

            return $category->load('translations');
        });
    }
}
