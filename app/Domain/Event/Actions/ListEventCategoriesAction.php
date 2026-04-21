<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;

class ListEventCategoriesAction
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
