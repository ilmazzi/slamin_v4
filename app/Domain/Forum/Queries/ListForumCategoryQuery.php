<?php

namespace Domain\Forum\Queries;

use Domain\Forum\Models\ForumCategory;
use Illuminate\Database\Eloquent\Collection;

class ListForumCategoryQuery
{
    public function execute(): Collection
    {
        return ForumCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->withCount(['posts as posts_count' => function ($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
