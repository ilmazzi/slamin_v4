<?php

namespace App\Http\Resources\Api\V1\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Group\Models\GroupMember */
class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role?->value,
            'status' => $this->status?->value,
            'joined_at' => $this->joined_at?->toISOString(),
            'handled_at' => $this->handled_at?->toISOString(),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'public_id' => $this->user->public_id,
                'nickname' => $this->user->profile?->nickname,
                'display_name' => $this->user->profile?->display_name,
            ] : null,
            'handled_by' => $this->handledBy ? [
                'id' => $this->handledBy->id,
                'public_id' => $this->handledBy->public_id,
                'nickname' => $this->handledBy->profile?->nickname,
                'display_name' => $this->handledBy->profile?->display_name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
