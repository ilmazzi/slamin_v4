<?php

namespace Domain\Poetry\Queries;

use Domain\Poetry\DTOs\ListPoemData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Domain\Shared\Support\AuthorNicknameFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPoemQuery
{
    public function execute(ListPoemData $data, ?int $authUserId = null): LengthAwarePaginator
    {
        $query = Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks', 'comments'])
            ->where('status', PoemStatus::Published)
            ->where('is_public', true);

        AuthorNicknameFilter::apply($query, $data->nickname);

        if ($data->poemCategoryId) {
            $query->where('poem_category_id', $data->poemCategoryId);
        }

        if ($data->poemTypeId) {
            $query->where('poem_type_id', $data->poemTypeId);
        }

        if (filled($data->language)) {
            $query->where('language', $data->language);
        }

        if (filled($data->search)) {
            $term = '%'.addcslashes($data->search, '%_\\').'%';
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('content', 'like', $term);
            });
        }

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        match ($data->sort) {
            'oldest' => $query->orderBy('published_at')->orderBy('created_at'),
            'popular' => $query->orderByDesc('likes_count')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('created_at'),
        };

        return $query->paginate($data->perPage);
    }
}
