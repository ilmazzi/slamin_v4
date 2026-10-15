<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Gig */
class PoemTranslationGigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'poem_id' => $this->giggable_id,
            'target_language' => $this->language,
            'proposed_price' => $this->proposed_price,
            'currency' => $this->currency,
            'deadline' => $this->deadline?->toISOString(),
            'special_requirements' => $this->special_requirements,
            'status' => $this->status?->value,
            'opened_at' => $this->opened_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'applications_count' => $this->whenCounted('applications'),
        ];
    }
}
