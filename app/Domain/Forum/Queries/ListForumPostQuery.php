<?php

namespace Domain\Forum\Queries;

use Domain\Forum\Enums\ForumPostStatus;
use Domain\Forum\Models\ForumPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListForumPostQuery
{
    public function execute(?string $categorySlug, string $sort, int $perPage, ?int $viewerId = null): LengthAwarePaginator
    {
        $query = ForumPost::query()
            ->with(['user.profile', 'category.translations'])
            ->where('status', ForumPostStatus::Published)
            ->whereHas('category', fn ($category) => $category->where('is_active', true));

        if ($categorySlug) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $categorySlug));
        }

        if ($viewerId) {
            $query->with(['votes' => fn ($votes) => $votes->where('user_id', $viewerId)]);
        }

        if ($categorySlug) {
            $query->orderByDesc('is_pinned');
        }

        match ($sort) {
            'top' => $query->orderByDesc('score')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage);
    }
}
