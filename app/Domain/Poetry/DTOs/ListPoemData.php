<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\ListPoemRequest;

class ListPoemData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?int $poemCategoryId,
        public readonly ?int $poemTypeId,
        public readonly ?string $search,
        public readonly ?string $language,
        public readonly string $sort,
        public readonly ?string $nickname,
    ) {}

    public static function fromRequest(ListPoemRequest $request): self
    {
        return new self(
            perPage: max(1, (int) ($request->validated('per_page') ?: 10)),
            poemCategoryId: $request->validated('poem_category_id'),
            poemTypeId: $request->validated('poem_type_id'),
            search: $request->validated('q'),
            language: $request->validated('language'),
            sort: $request->validated('sort') ?: 'newest',
            nickname: $request->validated('nickname'),
        );
    }
}
