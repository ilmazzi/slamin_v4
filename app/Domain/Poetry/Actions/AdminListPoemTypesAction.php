<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\PoemType;
use Illuminate\Support\Collection;

class AdminListPoemTypesAction
{
    public function execute(): Collection
    {
        return PoemType::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
