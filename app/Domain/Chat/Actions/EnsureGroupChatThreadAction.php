<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Enums\ChatThreadType;
use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Models\Group;
use Domain\Group\Support\GroupChatAudienceRules;
use Illuminate\Support\Facades\DB;

class EnsureGroupChatThreadAction
{
    public function __construct(
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    public function execute(Group $group, GroupChatAudience $audience, ?int $actorUserId = null): ChatThread
    {
        return DB::transaction(function () use ($group, $audience, $actorUserId) {
            $thread = ChatThread::query()->firstOrCreate(
                [
                    'group_id' => $group->id,
                    'audience' => $audience->value,
                ],
                [
                    'type' => ChatThreadType::Group,
                    'title' => GroupChatAudienceRules::title($group, $audience),
                    'is_enabled' => true,
                    'created_by_user_id' => $actorUserId,
                ],
            );

            if (! $thread->is_enabled) {
                $thread->update([
                    'is_enabled' => true,
                    'title' => GroupChatAudienceRules::title($group, $audience),
                ]);
            }

            $this->syncGroupChatMembersAction->execute($group, false);

            return $thread->fresh(['group', 'members.profile', 'latestMessage.user.profile']);
        });
    }
}
