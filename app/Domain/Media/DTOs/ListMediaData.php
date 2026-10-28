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
        public readonly ?string $nickname,
        public readonly ?int $excludeId = null,
        public readonly ?int $excludeUserId = null,
    ) {}

    public static function fromRequest(ListMediaRequest $request): self
    {
        $type = $request->validated('type');

        return new self(
            perPage: (int) ($request->validated('per_page') ?: 15),
            search: $request->validated('q'),
            type: is_string($type) && $type !== '' ? MediaType::from($type) : null,
            sort: $request->validated('sort') ?: 'newest',
            nickname: $request->validated('nickname'),
            excludeId: $request->validated('exclude_id'),
            excludeUserId: $request->validated('exclude_user_id'),
        );
    }
}
