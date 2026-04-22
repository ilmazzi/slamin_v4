<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\UpdateMediaItemRequest;
use Domain\Media\Enums\MediaVisibility;

class UpdateMediaItemData
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?MediaVisibility $visibility,
    ) {
    }

    public static function fromRequest(UpdateMediaItemRequest $request): self
    {
        return new self(
            title: $request->has('title') ? $request->validated('title') : null,
            description: $request->has('description') ? $request->validated('description') : null,
            visibility: $request->has('visibility')
                ? MediaVisibility::from($request->validated('visibility'))
                : null,
        );
    }
}
