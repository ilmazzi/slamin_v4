<?php

namespace App\Http\Resources\Api\V1\Forum;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Domain\Forum\Models\ForumPost */
class ForumPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user('sanctum') ?? $request->user();

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'status' => $this->status?->value,
            'is_locked' => (bool) $this->is_locked,
            'is_pinned' => (bool) $this->is_pinned,
            'score' => (int) $this->score,
            'comments_count' => (int) $this->comments_count,
            'my_vote' => $this->relationLoaded('votes')
                ? $this->votes->first()?->value
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'author' => [
                'id' => $this->user?->id,
                'public_id' => $this->user?->public_id,
                'nickname' => $this->user?->profile?->nickname,
                'display_name' => $this->user?->profile?->display_name,
            ],
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'slug' => $this->category->slug,
                'name' => $this->category->translatedName(),
            ]),
            'can_update' => $user ? $user->can('update', $this->resource) : false,
            'can_delete' => $user ? $user->can('delete', $this->resource) : false,
            'can_moderate' => $user ? $user->can('moderate', $this->resource) : false,
        ];
    }
}
