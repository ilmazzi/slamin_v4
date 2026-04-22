<?php

namespace App\Http\Resources\Api\V1\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Group\Models\GroupAnnouncement */
class GroupAnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'is_pinned' => $this->is_pinned,
            'published_at' => $this->published_at?->toISOString(),
            'author' => $this->author ? [
                'id' => $this->author->id,
                'public_id' => $this->author->public_id,
                'nickname' => $this->author->profile?->nickname,
                'display_name' => $this->author->profile?->display_name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
