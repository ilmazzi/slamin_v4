<?php

namespace Domain\Moderation\Actions;

use Domain\Poetry\Enums\PoemStatus;
use Domain\Poetry\Models\Poem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingReviewPoemsAction
{
    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        return Poem::query()
            ->with(['user.profile', 'category.translations', 'type.translations'])
            ->withCount(['likes', 'bookmarks'])
            ->where('status', PoemStatus::PendingReview)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
