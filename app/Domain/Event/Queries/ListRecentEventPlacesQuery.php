<?php

namespace Domain\Event\Queries;

use Domain\Event\Models\RecentEventPlace;
use Illuminate\Database\Eloquent\Collection;

class ListRecentEventPlacesQuery
{
    public function execute(int $userId, int $limit = 10): Collection
    {
        return RecentEventPlace::query()
            ->where('user_id', $userId)
            ->orderByDesc('last_used_at')
            ->limit($limit)
            ->get();
    }
}
