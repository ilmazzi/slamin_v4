<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemType;
use Illuminate\Support\Facades\DB;

class AdminStorePoemTypeAction
{
    public function execute(array $data, int $userId): PoemType
    {
        return DB::transaction(function () use ($data, $userId) {
            $type = PoemType::query()->create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $type->translations()->createMany($data['translations']);

            audit()->log(
                event: 'poem_type.created',
                userId: $userId,
                auditable: $type,
                newValues: [
                    'slug' => $type->slug,
                    'is_active' => $type->is_active,
                    'sort_order' => $type->sort_order,
                    'translations' => $data['translations'],
                ],
                metadata: [
                    'source' => 'admin_poem_type_store',
                ],
            );

            return $type->load('translations');
        });
    }
}
