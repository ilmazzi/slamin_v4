<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\EventParticipantInvite;
use Illuminate\Database\Eloquent\Collection;

class ListMyParticipantInvitesAction
{
    public function execute(int $userId): Collection
    {
        return EventParticipantInvite::query()
            ->with(['event', 'user.profile'])
            ->where('user_id', $userId)
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->get();
    }
}
