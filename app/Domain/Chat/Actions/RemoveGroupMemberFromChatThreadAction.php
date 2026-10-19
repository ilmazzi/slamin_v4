<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Domain\Group\Models\Group;

class RemoveGroupMemberFromChatThreadAction
{
    public function __construct(
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    public function execute(Group $group, int $userId): void
    {
        ChatThread::query()
            ->where('group_id', $group->id)
            ->get()
            ->each(function (ChatThread $thread) use ($userId) {
                $thread->memberRecords()->where('user_id', $userId)->delete();
            });

        $this->syncGroupChatMembersAction->execute($group);
    }
}
