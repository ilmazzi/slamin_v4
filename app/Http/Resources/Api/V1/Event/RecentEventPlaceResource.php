<?php

namespace App\Http\Resources\Api\V1\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Event\Models\RecentEventPlace */
class RecentEventPlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'place_name' => $this->place_name,
            'country_code' => $this->country_code,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'address_line' => $this->address_line,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'last_used_at' => $this->last_used_at?->toISOString(),
        ];
    }
}
