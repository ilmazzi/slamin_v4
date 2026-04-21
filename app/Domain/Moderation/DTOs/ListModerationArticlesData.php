<?php

namespace Domain\Moderation\DTOs;

use App\Http\Requests\Api\V1\Moderation\ListModerationArticlesRequest;

class ListModerationArticlesData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?string $query,
        public readonly ?int $articleCategoryId,
    ) {
    }

    public static function fromRequest(ListModerationArticlesRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            status: $request->validated('status'),
            query: $request->validated('q'),
            articleCategoryId: $request->validated('article_category_id'),
        );
    }
}
