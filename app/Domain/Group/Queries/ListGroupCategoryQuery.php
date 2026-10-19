<?php

namespace Domain\Group\Queries;

use Domain\Group\Models\GroupCategory;
use Illuminate\Database\Eloquent\Collection;

class ListGroupCategoryQuery
{
    public function execute(): Collection
    {
        return GroupCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
