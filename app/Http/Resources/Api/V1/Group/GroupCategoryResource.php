<?php

namespace App\Http\Resources\Api\V1\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Group\Models\GroupCategory */
class GroupCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->translatedName(),
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
        ];
    }
}
