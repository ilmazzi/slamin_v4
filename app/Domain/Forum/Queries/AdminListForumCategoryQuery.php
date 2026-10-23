<?php

namespace Domain\Forum\Queries;

use Domain\Forum\Models\ForumCategory;
use Illuminate\Database\Eloquent\Collection;

class AdminListForumCategoryQuery
{
    public function execute(): Collection
    {
        return ForumCategory::query()
            ->with('translations')
            ->withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
