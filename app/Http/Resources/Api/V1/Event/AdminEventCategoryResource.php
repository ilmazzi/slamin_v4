<?php

namespace App\Http\Resources\Api\V1\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Event\Models\EventCategory */
class AdminEventCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translatedName(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
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
