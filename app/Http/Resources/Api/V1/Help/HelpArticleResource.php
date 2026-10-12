<?php

namespace App\Http\Resources\Api\V1\Help;

use Domain\Help\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpArticle */
class HelpArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translatedTitle(),
            'body' => $this->translatedBody(),
            'sort_order' => $this->sort_order,
            'category' => $this->category
                ? new HelpCategoryResource($this->category)
                : null,
        ];
    }
}
