<?php

namespace Domain\Event\Queries;

use Domain\Event\Models\EventAudienceInvite;
use Illuminate\Database\Eloquent\Collection;

class ListMyAudienceInvitesQuery
{
    public function execute(int $userId): Collection
    {
        return EventAudienceInvite::query()
            ->with(['event', 'user.profile'])
            ->where('user_id', $userId)
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->get();
    }
}
