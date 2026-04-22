<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;

class MarkChatThreadReadAction
{
    public function execute(ChatThread $thread, int $userId): void
    {
        $timestamp = $thread->latestMessage?->created_at ?? now();

        $thread->memberRecords()
            ->where('user_id', $userId)
            ->update([
                'last_read_at' => $timestamp,
            ]);

        audit()->log(
            event: 'chat.thread_read',
            userId: $userId,
            auditable: $thread,
            newValues: [
                'chat_thread_id' => $thread->id,
                'last_read_at' => $timestamp?->toISOString(),
            ],
            metadata: [
                'source' => 'chat_thread_read',
            ],
        );
    }
}
