<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpCategory;
use Illuminate\Support\Collection;

class AdminListHelpCategoriesAction
{
    /**
     * @return Collection<int, HelpCategory>
     */
    public function execute(string $section = 'help'): Collection
    {
        return HelpCategory::query()
            ->with('translations')
            ->withCount('articles')
            ->where('section', $section)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
