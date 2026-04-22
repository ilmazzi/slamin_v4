<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListPendingGroupRequestsAction
{
    public function execute(Group $group): Collection
    {
        return $group->members()
            ->with(['user.profile', 'handledBy.profile'])
            ->where('status', GroupMemberStatus::Pending)
            ->orderBy('created_at')
            ->get();
    }
}
