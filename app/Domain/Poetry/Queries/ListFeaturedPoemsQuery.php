<?php

namespace Domain\Poetry\Queries;

use Domain\Poetry\DTOs\ListFeaturedPoemsData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListFeaturedPoemsQuery
{
    public function execute(ListFeaturedPoemsData $data): LengthAwarePaginator
    {
        $baseQuery = Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks', 'comments'])
            ->where('status', PoemStatus::Published)
            ->where('is_public', true);

        if ($data->poemCategoryId) {
            $baseQuery->where('poem_category_id', $data->poemCategoryId);
        }

        if ($data->poemTypeId) {
            $baseQuery->where('poem_type_id', $data->poemTypeId);
        }

        $featuredExists = (clone $baseQuery)
            ->where('is_featured', true)
            ->exists();

        if ($featuredExists) {
            return $baseQuery
                ->where('is_featured', true)
                ->orderBy('featured_rank')
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->paginate($data->perPage);
        }

        return $baseQuery
            ->inRandomOrder()
            ->paginate($data->perPage);
    }
}
