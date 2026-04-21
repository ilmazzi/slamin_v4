<?php

namespace Domain\Article\Actions;

use Domain\Article\Models\ArticleCategory;
use Illuminate\Support\Collection;

class ListArticleCategoriesAction
{
    public function execute(): Collection
    {
        return ArticleCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();
    }
}
