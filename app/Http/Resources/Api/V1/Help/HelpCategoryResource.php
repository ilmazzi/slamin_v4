<?php

namespace App\Http\Resources\Api\V1\Help;

use Domain\Help\Models\HelpCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpCategory */
class HelpCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translatedName(),
            'sort_order' => $this->sort_order,
        ];
    }
}
