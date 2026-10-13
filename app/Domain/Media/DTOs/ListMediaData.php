<?php

namespace Domain\Media\DTOs;

use App\Http\Requests\Api\V1\Media\ListMediaRequest;
use Domain\Media\Enums\MediaType;

class ListMediaData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?MediaType $type,
        public readonly string $sort,
    ) {}

    public static function fromRequest(ListMediaRequest $request): self
    {
        $type = $request->validated('type');

        return new self(
            perPage: (int) ($request->validated('per_page') ?: 15),
            search: $request->validated('q'),
            type: is_string($type) && $type !== '' ? MediaType::from($type) : null,
            sort: $request->validated('sort') ?: 'newest',
        );
    }
}
