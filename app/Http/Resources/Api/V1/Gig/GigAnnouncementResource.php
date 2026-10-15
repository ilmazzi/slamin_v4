<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GigAnnouncement */
class GigAnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gig_id' => $this->gig_id,
            'message' => $this->message,
            'status' => $this->status?->value,
            'published_at' => $this->published_at?->toISOString(),
            'author' => $this->whenLoaded('author', fn (): ?GigUserResource => $this->author ? new GigUserResource($this->author) : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
