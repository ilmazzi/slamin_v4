<?php

namespace Domain\Group\DTOs;

use App\Http\Requests\Api\V1\Group\ListGroupsRequest;

class ListGroupsData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $query,
    ) {
    }

    public static function fromRequest(ListGroupsRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            query: $request->validated('q'),
        );
    }
}
