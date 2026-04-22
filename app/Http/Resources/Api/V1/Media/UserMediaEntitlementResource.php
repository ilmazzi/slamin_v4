<?php

namespace App\Http\Resources\Api\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Media\Models\UserMediaEntitlement */
class UserMediaEntitlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'config' => $this->config ?? [],
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'is_active' => $this->isActive(),
            'feature_pack' => $this->featurePack
                ? new MediaFeaturePackResource($this->featurePack)
                : null,
            'granted_by' => [
                'id' => $this->grantedBy?->id,
                'public_id' => $this->grantedBy?->public_id,
                'nickname' => $this->grantedBy?->profile?->nickname,
                'display_name' => $this->grantedBy?->profile?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
