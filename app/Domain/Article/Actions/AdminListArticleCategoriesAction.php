<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\ArticleCategory;
use Illuminate\Support\Collection;

class AdminListArticleCategoriesAction
{
    public function execute(): Collection
    {
        return ArticleCategory::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
