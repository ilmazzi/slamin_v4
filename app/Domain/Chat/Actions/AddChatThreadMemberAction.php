<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddChatThreadMemberAction
{
    public function execute(ChatThread $thread, int $userId, int $actorUserId): ChatThread
    {
        if (! $thread->isGroup() || $thread->isAttachedGroup()) {
            throw ValidationException::withMessages([
                'thread' => [__('chat.validation.members_manage_only_free_groups')],
            ]);
        }

        User::query()->findOrFail($userId);

        return DB::transaction(function () use ($thread, $userId, $actorUserId) {
            $thread->memberRecords()->firstOrCreate(
                ['user_id' => $userId],
                ['joined_at' => now()],
            );

            audit()->log(
                event: 'chat.thread_member_added',
                userId: $actorUserId,
                auditable: $thread,
                newValues: [
                    'chat_thread_id' => $thread->id,
                    'member_user_id' => $userId,
                ],
                metadata: [
                    'source' => 'chat_thread_member_add',
                ],
            );

            return $thread->fresh()->load(['group', 'members.profile', 'memberRecords', 'latestMessage.user.profile']);
        });
    }
}
