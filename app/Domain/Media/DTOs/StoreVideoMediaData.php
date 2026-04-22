<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\StoreVideoMediaRequest;
use Domain\Media\Enums\MediaVisibility;
use Illuminate\Http\UploadedFile;

class StoreVideoMediaData
{
    /**
     * @param  string[]  $tags
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly MediaVisibility $visibility,
        public readonly UploadedFile $file,
        public readonly array $tags,
    ) {
    }

    public static function fromRequest(StoreVideoMediaRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            title: $request->validated('title'),
            description: $request->validated('description'),
            visibility: MediaVisibility::from($request->validated('visibility')),
            file: $request->file('file'),
            tags: array_values(array_filter(array_map('trim', (array) $request->input('tags', [])))),
        );
    }
}
