<?php

namespace Domain\Event\Queries;

use Domain\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;

class ListEventCategoryQuery
{
    public function execute(): Collection
    {
        return EventCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
