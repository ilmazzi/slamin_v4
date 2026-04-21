<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;

class AdminListEventCategoriesAction
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
