<?php

namespace Domain\Chat\Actions;

use Domain\Group\Models\Group;

class AddGroupMemberToChatThreadAction
{
    public function __construct(
        private readonly EnsureGroupChatThreadAction $ensureGroupChatThreadAction,
    ) {
    }

    public function execute(Group $group, int $userId, ?int $actorUserId = null): void
    {
        $thread = $this->ensureGroupChatThreadAction->execute($group, $actorUserId);

        $thread->memberRecords()->firstOrCreate(
            ['user_id' => $userId],
            ['joined_at' => now()],
        );
    }
}
