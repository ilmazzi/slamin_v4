<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingReviewEventsAction
{
    public function execute(int $perPage = 10): LengthAwarePaginator
    {
        return Event::query()
            ->with([
                'user.profile',
                'category.translations',
                'festival',
                'occurrences',
                'venueDetail',
                'onlineDetail',
            ])
            ->where('status', EventStatus::PendingReview)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
