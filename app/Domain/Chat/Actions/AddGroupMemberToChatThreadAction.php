<?php

namespace Domain\Chat\Actions;

use Domain\Group\Models\Group;

class AddGroupMemberToChatThreadAction
{
    public function __construct(
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    public function execute(Group $group, int $userId, ?int $actorUserId = null): void
    {
        unset($userId, $actorUserId);

        $this->syncGroupChatMembersAction->execute($group);
    }
}
