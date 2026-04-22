<?php

namespace Domain\Chat\Actions;

use Domain\Chat\DTOs\StoreGroupChatThreadData;
use Domain\Chat\Enums\ChatThreadType;
use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class StoreGroupChatThreadAction
{
    public function execute(int $actorUserId, StoreGroupChatThreadData $data): ChatThread
    {
        return DB::transaction(function () use ($actorUserId, $data) {
            $memberIds = collect($data->userIds)
                ->push($actorUserId)
                ->unique()
                ->values()
                ->all();

            User::query()
                ->whereIn('id', $memberIds)
                ->get();

            $thread = ChatThread::query()->create([
                'type' => ChatThreadType::Group,
                'title' => $data->title,
                'created_by_user_id' => $actorUserId,
            ]);

            foreach ($memberIds as $memberId) {
                $thread->memberRecords()->create([
                    'user_id' => $memberId,
                    'joined_at' => now(),
                    'last_read_at' => $memberId === $actorUserId ? now() : null,
                ]);
            }

            audit()->log(
                event: 'chat.group_thread_created',
                userId: $actorUserId,
                auditable: $thread,
                newValues: [
                    'chat_thread_id' => $thread->id,
                    'title' => $thread->title,
                    'member_count' => count($memberIds),
                ],
                metadata: [
                    'source' => 'chat_group_thread_store',
                ],
            );

            return $thread->load(['members.profile', 'memberRecords', 'latestMessage.user.profile']);
        });
    }
}
