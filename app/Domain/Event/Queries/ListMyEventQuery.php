<?php

namespace Domain\Event\Queries;

use Domain\Event\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyEventQuery
{
    public function execute(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Event::query()
            ->with([
                'user.profile',
                'category.translations',
                'festival',
                'occurrences',
                'availabilityPoll',
                'availabilityOptions.votes',
                'venueDetail',
                'onlineDetail',
            ])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
