<?php

namespace Domain\Help\Actions;

use Domain\Help\Models\HelpArticle;
use Domain\Help\Models\HelpCategory;
use Illuminate\Support\Collection;

class ListHelpAction
{
    /**
     * @return array{categories: Collection<int, HelpCategory>, articles: Collection<int, HelpArticle>}
     */
    public function execute(?string $query = null, ?string $categorySlug = null, string $section = 'help'): array
    {
        $search = trim((string) $query);
        $like = $search !== '' ? '%'.addcslashes($search, '%_\\').'%' : null;

        $categories = HelpCategory::query()
            ->with('translations')
            ->where('is_active', true)
            ->where('section', $section)
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->get();

        $articles = HelpArticle::query()
            ->with(['translations', 'category.translations'])
            ->where('is_published', true)
            ->whereHas('category', fn ($category) => $category
                ->where('is_active', true)
                ->where('section', $section))
            ->when($categorySlug, function ($builder) use ($categorySlug) {
                $builder->whereHas('category', fn ($category) => $category->where('slug', $categorySlug));
            })
            ->when($like, function ($builder) use ($like) {
                $builder->where(function ($inner) use ($like) {
                    $inner->whereHas('translations', function ($translation) use ($like) {
                        $translation->where('title', 'like', $like)
                            ->orWhere('body', 'like', $like);
                    })->orWhereHas('category.translations', function ($translation) use ($like) {
                        $translation->where('name', 'like', $like);
                    });
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'categories' => $categories,
            'articles' => $articles,
        ];
    }
}
