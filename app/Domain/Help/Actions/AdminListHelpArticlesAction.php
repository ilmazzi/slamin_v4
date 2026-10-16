<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;
use Illuminate\Support\Collection;

class AdminListHelpArticlesAction
{
    /**
     * @return Collection<int, HelpArticle>
     */
    public function execute(string $section = 'help'): Collection
    {
        return HelpArticle::query()
            ->with(['translations', 'category.translations'])
            ->whereHas('category', fn ($category) => $category->where('section', $section))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
