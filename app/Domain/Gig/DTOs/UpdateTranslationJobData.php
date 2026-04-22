<?php

namespace Domain\Gig\DTOs;

use App\Http\Requests\Api\V1\Gig\UpdateTranslationJobRequest;

class UpdateTranslationJobData
{
    public function __construct(
        public readonly ?string $translatedContent,
        public readonly bool $translatedContentProvided,
        public readonly ?string $deadline,
        public readonly bool $deadlineProvided,
    ) {
    }

    public static function fromRequest(UpdateTranslationJobRequest $request): self
    {
        return new self(
            translatedContent: $request->filled('translated_content') ? $request->string('translated_content')->toString() : null,
            translatedContentProvided: $request->exists('translated_content'),
            deadline: $request->filled('deadline') ? $request->string('deadline')->toString() : null,
            deadlineProvided: $request->exists('deadline'),
        );
    }
}
