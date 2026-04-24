<?php

namespace Domain\Article\DTOs;

use App\Http\Requests\Api\V1\Article\ListFeaturedArticlesRequest;

class ListFeaturedArticlesData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?int $articleCategoryId,
    ) {
    }

    public static function fromRequest(ListFeaturedArticlesRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 1),
            articleCategoryId: $request->validated('article_category_id'),
        );
    }
}
