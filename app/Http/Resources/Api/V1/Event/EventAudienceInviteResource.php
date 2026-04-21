<?php

namespace App\Http\Resources\Api\V1\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Event\Models\EventAudienceInvite */
class EventAudienceInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'event' => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
                'status' => $this->event->status?->value,
            ] : null,
            'invited_user' => $this->user ? [
                'id' => $this->user->id,
                'public_id' => $this->user->public_id,
                'nickname' => $this->user->profile?->nickname,
                'display_name' => $this->user->profile?->display_name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
