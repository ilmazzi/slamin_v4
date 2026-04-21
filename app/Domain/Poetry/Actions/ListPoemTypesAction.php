<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemType;
use Illuminate\Support\Collection;

class ListPoemTypesAction
{
    public function execute(): Collection
    {
        return PoemType::query()
            ->with('translations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
