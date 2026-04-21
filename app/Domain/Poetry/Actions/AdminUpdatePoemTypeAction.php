<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemType;
use Illuminate\Support\Facades\DB;

class AdminUpdatePoemTypeAction
{
    public function execute(PoemType $type, array $data, int $userId): PoemType
    {
        return DB::transaction(function () use ($type, $data, $userId) {
            $oldValues = [
                'slug' => $type->slug,
                'is_active' => $type->is_active,
                'sort_order' => $type->sort_order,
                'translations' => $type->translations()
                    ->orderBy('locale')
                    ->get(['locale', 'name'])
                    ->map(fn ($translation) => [
                        'locale' => $translation->locale,
                        'name' => $translation->name,
                    ])->values()->all(),
            ];

            $type->update([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $locales = collect($data['translations'])
                ->pluck('locale')
                ->all();

            $type->translations()
                ->whereNotIn('locale', $locales)
                ->delete();

            foreach ($data['translations'] as $translation) {
                $type->translations()->updateOrCreate(
                    ['locale' => $translation['locale']],
                    ['name' => $translation['name']]
                );
            }

            audit()->log(
                event: 'poem_type.updated',
                userId: $userId,
                auditable: $type,
                oldValues: $oldValues,
                newValues: [
                    'slug' => $type->slug,
                    'is_active' => $type->is_active,
                    'sort_order' => $type->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_poem_type_update',
                ],
            );

            return $type->load('translations');
        });
    }
}
