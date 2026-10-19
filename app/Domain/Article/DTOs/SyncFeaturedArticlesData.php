<?php

namespace Domain\Article\DTOs;

use App\Http\Requests\Api\V1\Admin\SyncFeaturedArticlesRequest;

class SyncFeaturedArticlesData
{
    /**
     * @param  list<int>  $articleIds
     */
    public function __construct(
        public readonly array $articleIds,
    ) {}

    public static function fromRequest(SyncFeaturedArticlesRequest $request): self
    {
        return new self(
            articleIds: array_values(array_map(
                'intval',
                $request->validated('article_ids', []),
            )),
        );
    }
}
