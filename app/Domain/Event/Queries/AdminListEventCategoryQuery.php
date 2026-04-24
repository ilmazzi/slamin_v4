<?php

namespace Domain\Event\Queries;

use Domain\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;

class AdminListEventCategoryQuery
{
    public function execute(): Collection
    {
        return EventCategory::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
