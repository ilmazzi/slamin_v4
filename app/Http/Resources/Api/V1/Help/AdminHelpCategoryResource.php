<?php

namespace App\Http\Resources\Api\V1\Help;

use Domain\Help\Models\HelpCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpCategory */
class AdminHelpCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'section' => $this->section,
            'name' => $this->translatedName(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'articles_count' => (int) ($this->articles_count ?? 0),
            'translations' => $this->translations
                ->sortBy('locale')
                ->values()
                ->map(fn ($translation) => [
                    'locale' => $translation->locale,
                    'name' => $translation->name,
                ]),
        ];
    }
}
