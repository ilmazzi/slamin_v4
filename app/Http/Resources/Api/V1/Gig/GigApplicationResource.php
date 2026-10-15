<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\GigApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GigApplication */
class GigApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gig_id' => $this->gig_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'status' => $this->status?->value,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'withdrawn_at' => $this->withdrawn_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn (): ?GigUserResource => $this->user ? new GigUserResource($this->user) : null),
            'gig' => $this->whenLoaded('gig', fn (): ?GigResource => $this->gig ? new GigResource($this->gig) : null),
            'negotiation' => $this->whenLoaded('negotiations', function (): ?array {
                $negotiation = $this->negotiations->first();

                if ($negotiation === null) {
                    return null;
                }

                $job = $negotiation->relationLoaded('translationJob') ? $negotiation->translationJob : null;

                return [
                    'id' => $negotiation->id,
                    'status' => $negotiation->status?->value,
                    'translation_job' => $job === null ? null : [
                        'id' => $job->id,
                        'status' => $job->status?->value,
                    ],
                ];
            }),
        ];
    }
}
