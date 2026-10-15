<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\TranslationComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TranslationComment */
class TranslationCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation_job_id' => $this->translation_job_id,
            'comment_target' => $this->comment_target?->value,
            'target_locator' => $this->target_locator,
            'content' => $this->content,
            'status' => $this->status?->value,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'author_user_id' => $this->author_user_id,
            'author' => $this->whenLoaded('author', fn (): ?GigUserResource => $this->author ? new GigUserResource($this->author) : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
