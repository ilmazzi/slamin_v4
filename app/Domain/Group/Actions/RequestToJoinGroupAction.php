<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;

class RequestToJoinGroupAction
{
    public function __construct(
        private readonly FollowGroupAction $followGroupAction,
    ) {}

    public function execute(Group $group, int $userId): GroupMember
    {
        return $this->followGroupAction->execute($group, $userId);
    }
}
