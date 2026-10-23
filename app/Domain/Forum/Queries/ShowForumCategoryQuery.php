<?php

namespace Domain\Forum\Queries;

use Domain\Forum\Models\ForumCategory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowForumCategoryQuery
{
    public function execute(string $slug): ForumCategory
    {
        $category = ForumCategory::query()
            ->with('translations')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['posts as posts_count' => function ($query) {
                $query->where('status', 'published');
            }])
            ->first();

        if (! $category) {
            throw new NotFoundHttpException;
        }

        return $category;
    }
}
