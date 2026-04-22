<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;

class ShowMyGroupMembershipAction
{
    public function execute(Group $group, int $userId): ?GroupMember
    {
        return $group->members()
            ->with(['user.profile', 'handledBy.profile'])
            ->where('user_id', $userId)
            ->first();
    }
}
