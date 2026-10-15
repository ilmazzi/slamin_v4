<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\GigNegotiationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GigNegotiationEvent */
class GigNegotiationEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type?->value,
            'price' => $this->price,
            'currency' => $this->currency,
            'deadline' => $this->deadline?->toISOString(),
            'note' => $this->note,
            'author_user_id' => $this->author_user_id,
            'author' => $this->whenLoaded('author', fn (): ?GigUserResource => $this->author ? new GigUserResource($this->author) : null),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
