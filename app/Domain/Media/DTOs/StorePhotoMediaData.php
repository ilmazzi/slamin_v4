<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\StorePhotoMediaRequest;
use Domain\Media\Enums\MediaVisibility;
use Illuminate\Http\UploadedFile;

class StorePhotoMediaData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly MediaVisibility $visibility,
        public readonly UploadedFile $file,
    ) {
    }

    public static function fromRequest(StorePhotoMediaRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            title: $request->validated('title'),
            description: $request->validated('description'),
            visibility: MediaVisibility::from($request->validated('visibility')),
            file: $request->file('file'),
        );
    }
}
