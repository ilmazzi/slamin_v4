<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveChatThreadMemberAction
{
    public function execute(ChatThread $thread, int $userId, int $actorUserId): ChatThread
    {
        if (! $thread->isGroup() || $thread->isAttachedGroup()) {
            throw ValidationException::withMessages([
                'thread' => [__('chat.validation.members_manage_only_free_groups')],
            ]);
        }

        if ($thread->created_by_user_id === $userId) {
            throw ValidationException::withMessages([
                'user_id' => [__('chat.validation.cannot_remove_thread_creator')],
            ]);
        }

        return DB::transaction(function () use ($thread, $userId, $actorUserId) {
            $thread->memberRecords()
                ->where('user_id', $userId)
                ->delete();

            audit()->log(
                event: 'chat.thread_member_removed',
                userId: $actorUserId,
                auditable: $thread,
                newValues: [
                    'chat_thread_id' => $thread->id,
                    'member_user_id' => $userId,
                ],
                metadata: [
                    'source' => 'chat_thread_member_remove',
                ],
            );

            return $thread->fresh()->load(['group', 'members.profile', 'memberRecords', 'latestMessage.user.profile']);
        });
    }
}
