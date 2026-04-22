<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Enums\ChatThreadType;
use Domain\Chat\Models\ChatThread;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnsureDirectChatThreadAction
{
    public function execute(int $actorUserId, int $targetUserId): ChatThread
    {
        if ($actorUserId === $targetUserId) {
            throw ValidationException::withMessages([
                'user_id' => [__('chat.validation.self_direct_thread')],
            ]);
        }

        [$firstId, $secondId] = collect([$actorUserId, $targetUserId])
            ->sort()
            ->values()
            ->all();

        $directKey = "{$firstId}:{$secondId}";

        return DB::transaction(function () use ($actorUserId, $targetUserId, $directKey) {
            $thread = ChatThread::query()->firstOrCreate(
                ['direct_key' => $directKey],
                [
                    'type' => ChatThreadType::Direct,
                    'created_by_user_id' => $actorUserId,
                ],
            );

            foreach ([$actorUserId, $targetUserId] as $userId) {
                $thread->memberRecords()->firstOrCreate(
                    ['user_id' => $userId],
                    ['joined_at' => now()],
                );
            }

            if ($thread->wasRecentlyCreated) {
                audit()->log(
                    event: 'chat.direct_thread_created',
                    userId: $actorUserId,
                    auditable: $thread,
                    newValues: [
                        'chat_thread_id' => $thread->id,
                        'target_user_id' => $targetUserId,
                    ],
                    metadata: [
                        'source' => 'chat_direct_thread_store',
                    ],
                );
            }

            return $thread->load(['members.profile', 'latestMessage.user.profile']);
        });
    }
}
