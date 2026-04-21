<?php

namespace Domain\Moderation\DTOs;

use App\Http\Requests\Api\V1\Moderation\ListModerationPoemsRequest;

class ListModerationPoemsData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?string $query,
        public readonly ?int $poemCategoryId,
        public readonly ?int $poemTypeId,
    ) {
    }

    public static function fromRequest(ListModerationPoemsRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            status: $request->validated('status'),
            query: $request->validated('q'),
            poemCategoryId: $request->validated('poem_category_id'),
            poemTypeId: $request->validated('poem_type_id'),
        );
    }
}
