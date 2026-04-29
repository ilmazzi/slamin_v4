<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\ListFeaturedPoemsRequest;

class ListFeaturedPoemsData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?int $poemCategoryId,
        public readonly ?int $poemTypeId,
    ) {
    }

    public static function fromRequest(ListFeaturedPoemsRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            poemCategoryId: $request->validated('poem_category_id'),
            poemTypeId: $request->validated('poem_type_id'),
        );
    }
}
