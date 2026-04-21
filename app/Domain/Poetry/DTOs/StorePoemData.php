<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\StorePoemRequest;
use Domain\Poetry\Enums\PoemStatus;
use Illuminate\Http\UploadedFile;

class StorePoemData
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $poemCategoryId,
        public readonly ?int $poemTypeId,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $description,
        public readonly ?UploadedFile $image,
        public readonly string $language,
        public readonly PoemStatus $status,
        public readonly bool $isPublic,
        public readonly ?string $publishedAt,
    ) {
    }

    public static function fromRequest(StorePoemRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            poemCategoryId: $request->validated('poem_category_id'),
            poemTypeId: $request->validated('poem_type_id'),
            title: $request->validated('title'),
            content: $request->validated('content'),
            description: $request->validated('description'),
            image: $request->file('image'),
            language: $request->validated('language', 'it'),
            status: $request->validated('status')
                ? PoemStatus::from($request->validated('status'))
                : PoemStatus::Draft,
            isPublic: (bool) $request->validated('is_public', true),
            publishedAt: $request->validated('published_at'),
        );
    }
}
