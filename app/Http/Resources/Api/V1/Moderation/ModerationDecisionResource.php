<?php

namespace App\Http\Resources\Api\V1\Moderation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Moderation\Models\ModerationDecision */
class ModerationDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision' => $this->decision?->value,
            'note' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
            'moderator' => [
                'id' => $this->moderator?->id,
                'public_id' => $this->moderator?->public_id,
                'nickname' => $this->moderator?->profile?->nickname,
                'display_name' => $this->moderator?->profile?->display_name,
            ],
        ];
    }
}
