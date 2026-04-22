<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListGroupMembersAction
{
    public function execute(Group $group): Collection
    {
        return $group->members()
            ->with(['user.profile', 'handledBy.profile'])
            ->where('status', 'active')
            ->orderByRaw("case when role = 'owner' then 0 when role = 'admin' then 1 when role = 'moderator' then 2 else 3 end")
            ->orderBy('created_at')
            ->get();
    }
}
