<?php

namespace App\Http\Resources\Api\V1\Help;

use Domain\Help\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpArticle */
class AdminHelpArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translatedTitle(),
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'help_category_id' => $this->help_category_id,
            'category' => $this->category
                ? new AdminHelpCategoryResource($this->category)
                : null,
            'translations' => $this->translations
                ->sortBy('locale')
                ->values()
                ->map(fn ($translation) => [
                    'locale' => $translation->locale,
                    'title' => $translation->title,
                    'body' => $translation->body,
                ]),
        ];
    }
}
