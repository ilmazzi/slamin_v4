<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;
use Illuminate\Support\Collection;

class AdminListHelpArticlesAction
{
    /**
     * @return Collection<int, HelpArticle>
     */
    public function execute(): Collection
    {
        return HelpArticle::query()
            ->with(['translations', 'category.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
