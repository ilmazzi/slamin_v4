<?php

namespace App\Http\Resources\Api\V1\Forum;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Forum\Models\ForumCategory */
class ForumCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translatedName(),
            'description' => $this->translatedDescription(),
            'sort_order' => $this->sort_order,
            'posts_count' => (int) ($this->posts_count ?? 0),
        ];
    }
}
