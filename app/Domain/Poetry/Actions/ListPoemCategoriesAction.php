<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemCategory;
use Illuminate\Support\Collection;

class ListPoemCategoriesAction
{
    public function execute(): Collection
    {
        return PoemCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
