<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\StoreTranslationCommentRequest;
use Domain\Gig\Enums\TranslationCommentTarget;

class StoreTranslationCommentData
{
    public function __construct(
        public readonly TranslationCommentTarget $commentTarget,
        public readonly ?string $targetLocator,
        public readonly string $content,
    ) {
    }

    public static function fromRequest(StoreTranslationCommentRequest $request): self
    {
        return new self(
            commentTarget: TranslationCommentTarget::from($request->string('comment_target')->toString()),
            targetLocator: $request->filled('target_locator') ? $request->string('target_locator')->toString() : null,
            content: $request->string('content')->toString(),
        );
    }
}
