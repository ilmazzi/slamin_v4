<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdateTranslationCommentRequest;

class UpdateTranslationCommentData
{
    public function __construct(
        public readonly ?string $content,
        public readonly bool $contentProvided,
    ) {
    }

    public static function fromRequest(UpdateTranslationCommentRequest $request): self
    {
        return new self(
            content: $request->filled('content') ? $request->string('content')->toString() : null,
            contentProvided: $request->exists('content'),
        );
    }
}
