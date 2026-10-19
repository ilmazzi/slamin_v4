<?php

namespace App\Http\Resources\Api\V1\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Group\Models\GroupAffiliation */
class GroupAffiliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'parent_group_id' => $this->parent_group_id,
            'child_group_id' => $this->child_group_id,
            'initiated_by_group_id' => $this->initiated_by_group_id,
            'parent' => $this->summary($this->parent),
            'child' => $this->summary($this->child),
            'initiated_by' => $this->summary($this->initiatedBy),
            'created_at' => $this->created_at?->toISOString(),
            'handled_at' => $this->handled_at?->toISOString(),
        ];
    }

    private function summary($group): ?array
    {
        if (! $group) {
            return null;
        }

        return [
            'id' => $group->id,
            'slug' => $group->slug,
            'name' => $group->name,
            'category' => $group->category ? [
                'id' => $group->category->id,
                'name' => $group->category->translatedName(),
            ] : null,
        ];
    }
}
