<?php

namespace Domain\Article\DTOs;

use App\Http\Requests\Api\V1\Article\ListArticleRequest;

class ListArticleData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?int $articleCategoryId,
    ) {
    }

    public static function fromRequest(ListArticleRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            articleCategoryId: $request->validated('article_category_id'),
        );
    }
}
