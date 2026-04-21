<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyEventsAction
{
    public function execute(int $userId, int $perPage = 10): LengthAwarePaginator
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
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
