<?php

namespace App\Http\Resources\Api\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Media\Models\MediaFeaturePack */
class MediaFeaturePackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'config' => $this->config ?? [],
            'is_active' => (bool) $this->is_active,
            'price_amount' => $this->price_amount,
            'currency' => $this->currency,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
