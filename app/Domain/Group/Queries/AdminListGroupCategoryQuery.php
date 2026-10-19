<?php

namespace Domain\Group\Queries;

use Domain\Group\Models\GroupCategory;
use Illuminate\Database\Eloquent\Collection;

class AdminListGroupCategoryQuery
{
    public function execute(): Collection
    {
        return GroupCategory::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
