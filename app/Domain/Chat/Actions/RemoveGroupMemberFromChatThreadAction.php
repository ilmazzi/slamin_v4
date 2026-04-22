<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Domain\Group\Models\Group;

class RemoveGroupMemberFromChatThreadAction
{
    public function execute(Group $group, int $userId): void
    {
        $thread = ChatThread::query()
            ->where('group_id', $group->id)
            ->first();

        if (! $thread) {
            return;
        }

        $thread->memberRecords()
            ->where('user_id', $userId)
            ->delete();
    }
}
