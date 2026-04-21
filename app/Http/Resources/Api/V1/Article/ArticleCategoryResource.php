<?php

namespace App\Http\Resources\Api\V1\Article;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Article\Models\ArticleCategory */
class ArticleCategoryResource extends JsonResource
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
