<?php

namespace App\Http\Resources\Api\V1\Comment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Comment\Models\Comment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'score' => (int) ($this->score ?? 0),
            'my_vote' => $this->relationLoaded('votes')
                ? $this->votes->first()?->value
                : null,
            'replies_count' => (int) ($this->replies_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
