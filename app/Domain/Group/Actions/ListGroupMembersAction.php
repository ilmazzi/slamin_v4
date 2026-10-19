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
            ->orderByRaw("case when role = 'admin' then 0 when role = 'editor' then 1 else 2 end")
            ->orderBy('created_at')
            ->get();
    }
}
