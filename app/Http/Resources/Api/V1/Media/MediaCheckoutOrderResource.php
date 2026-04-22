<?php

namespace App\Http\Resources\Api\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Media\Models\MediaCheckoutOrder */
class MediaCheckoutOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'provider' => $this->provider,
            'provider_checkout_session_id' => $this->provider_checkout_session_id,
            'provider_payment_intent_id' => $this->provider_payment_intent_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'feature_code' => $this->feature_code,
            'feature_name' => $this->feature_name,
            'feature_config' => $this->feature_config ?? [],
            'payload' => $this->payload ?? [],
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'feature_pack' => $this->featurePack
                ? new MediaFeaturePackResource($this->featurePack)
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
