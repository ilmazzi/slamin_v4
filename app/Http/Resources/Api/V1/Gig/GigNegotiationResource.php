<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\GigNegotiation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GigNegotiation */
class GigNegotiationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gig_id' => $this->gig_id,
            'gig_application_id' => $this->gig_application_id,
            'owner_user_id' => $this->owner_user_id,
            'translator_user_id' => $this->translator_user_id,
            'status' => $this->status?->value,
            'agreed_price' => $this->agreed_price,
            'currency' => $this->currency,
            'agreed_deadline' => $this->agreed_deadline?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'events_count' => $this->when(
                $this->events_count !== null || $this->relationLoaded('events'),
                fn (): int => (int) ($this->events_count ?? $this->events->count()),
            ),
            'events' => GigNegotiationEventResource::collection($this->whenLoaded('events')),
            'owner' => $this->whenLoaded('owner', fn (): ?GigUserResource => $this->owner ? new GigUserResource($this->owner) : null),
            'translator' => $this->whenLoaded('translator', fn (): ?GigUserResource => $this->translator ? new GigUserResource($this->translator) : null),
            'gig' => $this->whenLoaded('gig', fn (): ?GigResource => $this->gig ? new GigResource($this->gig) : null),
            'translation_job' => $this->whenLoaded('translationJob', fn (): ?array => $this->translationJob ? [
                'id' => $this->translationJob->id,
                'status' => $this->translationJob->status?->value,
            ] : null),
        ];
    }
}
