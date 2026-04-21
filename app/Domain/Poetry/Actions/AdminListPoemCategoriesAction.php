<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemCategory;
use Illuminate\Support\Collection;

class AdminListPoemCategoriesAction
{
    public function execute(): Collection
    {
        return PoemCategory::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
