<?php

namespace App\Http\Resources\Api\V1\ContentTranslation;

use Domain\ContentTranslation\Models\ContentTranslation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContentTranslation */
class ContentTranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'locale' => $this->locale,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
