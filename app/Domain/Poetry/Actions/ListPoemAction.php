<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\DTOs\ListPoemData;
use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPoemAction
{
    public function execute(ListPoemData $data, ?int $authUserId = null): LengthAwarePaginator
    {
        $query = Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('status', PoemStatus::Published)
            ->where('is_public', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($data->poemCategoryId) {
            $query->where('poem_category_id', $data->poemCategoryId);
        }

        if ($data->poemTypeId) {
            $query->where('poem_type_id', $data->poemTypeId);
        }

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        return $query->paginate($data->perPage);
    }
}
