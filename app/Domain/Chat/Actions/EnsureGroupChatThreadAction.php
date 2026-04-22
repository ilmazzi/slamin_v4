<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Enums\ChatThreadType;
use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Illuminate\Support\Facades\DB;

class EnsureGroupChatThreadAction
{
    public function execute(Group $group, ?int $actorUserId = null): ChatThread
    {
        return DB::transaction(function () use ($group, $actorUserId) {
            $thread = ChatThread::query()->firstOrCreate(
                [
                    'group_id' => $group->id,
                ],
                [
                    'type' => ChatThreadType::Group,
                    'created_by_user_id' => $actorUserId,
                ],
            );

            $activeMemberIds = $group->members()
                ->where('status', GroupMemberStatus::Active)
                ->pluck('user_id');

            foreach ($activeMemberIds as $memberId) {
                $thread->memberRecords()->firstOrCreate(
                    ['user_id' => (int) $memberId],
                    ['joined_at' => now()],
                );
            }

            audit()->log(
                event: 'chat.group_thread_ensured',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'chat_thread_id' => $thread->id,
                ],
                metadata: [
                    'source' => 'group_chat_thread_ensure',
                ],
            );

            return $thread->load(['group', 'members.profile', 'latestMessage.user.profile']);
        });
    }
}
