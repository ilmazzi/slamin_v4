<?php

namespace App\Http\Resources\Api\V1\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Media\Models\MediaSnap */
class MediaSnapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_item_id' => $this->media_item_id,
            'timestamp_seconds' => $this->timestamp_seconds,
            'kind' => $this->kind?->value,
            'comment' => $this->comment,
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
