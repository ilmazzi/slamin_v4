<?php

namespace App\Http\Resources\Api\V1\Gig;

use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TranslationJob */
class TranslationJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gig_id' => $this->gig_id,
            'gig_negotiation_id' => $this->gig_negotiation_id,
            'poem_id' => $this->poem_id,
            'owner_user_id' => $this->owner_user_id,
            'translator_user_id' => $this->translator_user_id,
            'source_language' => $this->source_language,
            'target_language' => $this->target_language,
            'agreed_price' => $this->agreed_price,
            'currency' => $this->currency,
            'deadline' => $this->deadline?->toISOString(),
            'translated_content' => $this->translated_content,
            'status' => $this->status?->value,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'comments_count' => $this->when(
                $this->comments_count !== null || $this->relationLoaded('comments'),
                fn (): int => (int) ($this->comments_count ?? $this->comments->count()),
            ),
            'poem' => $this->whenLoaded('poem', fn (): ?array => $this->poem ? [
                'id' => $this->poem->id,
                'title' => $this->poem->title,
                'slug' => $this->poem->slug,
                'content' => $this->poem->content,
                'language' => $this->poem->language,
            ] : null),
            'owner' => $this->whenLoaded('owner', fn (): ?GigUserResource => $this->owner ? new GigUserResource($this->owner) : null),
            'translator' => $this->whenLoaded('translator', fn (): ?GigUserResource => $this->translator ? new GigUserResource($this->translator) : null),
            'comments' => TranslationCommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
