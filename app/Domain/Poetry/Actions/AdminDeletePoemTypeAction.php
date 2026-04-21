<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemType;

class AdminDeletePoemTypeAction
{
    public function execute(PoemType $type, int $userId): bool
    {
        if ($type->poems()->exists()) {
            return false;
        }

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

        $type->delete();

        audit()->log(
            event: 'poem_type.deleted',
            userId: $userId,
            auditable: $type,
            oldValues: $oldValues,
            metadata: [
                'source' => 'admin_poem_type_delete',
            ],
        );

        return true;
    }
}
