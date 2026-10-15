<?php

namespace Domain\Poetry\Actions;

use Domain\Poetry\Models\Poem;

class ShowPoemAction
{
    public function execute(string $slug, ?int $authUserId = null): Poem
    {
        $query = Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('slug', $slug)
            ->visibleTo($authUserId);

        if ($authUserId) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $authUserId),
                'bookmarks as is_bookmarked' => fn ($q) => $q->where('user_id', $authUserId),
            ]);
        }

        return $query->firstOrFail();
    }
}
