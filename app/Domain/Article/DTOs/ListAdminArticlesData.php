<?php

namespace Domain\Article\DTOs;

use App\Http\Requests\Api\V1\Admin\ListAdminArticlesRequest;

class ListAdminArticlesData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $search,
    ) {}

    public static function fromRequest(ListAdminArticlesRequest $request): self
    {
        $search = trim((string) $request->validated('search', ''));

        return new self(
            perPage: (int) $request->validated('per_page', 20),
            search: $search !== '' ? $search : null,
        );
    }
}
