<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\SyncGroupChatMembersAction;
use Domain\Chat\Enums\ChatThreadType;
use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Models\Group;
use Domain\Group\Support\GroupChatAudienceRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateGroupChatThreadsAction
{
    public function __construct(
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    /**
     * @param  list<GroupChatAudience>  $audiences
     */
    public function execute(Group $group, array $audiences, int $actorUserId): Collection
    {
        return DB::transaction(function () use ($group, $audiences, $actorUserId) {
            $wanted = collect($audiences)
                ->filter(fn ($audience) => $audience instanceof GroupChatAudience)
                ->unique(fn (GroupChatAudience $audience) => $audience->value);

            foreach (GroupChatAudience::cases() as $audience) {
                $thread = ChatThread::query()->firstOrNew([
                    'group_id' => $group->id,
                    'audience' => $audience->value,
                ]);
                $shouldEnable = $wanted->contains(fn (GroupChatAudience $item) => $item === $audience);

                if (! $shouldEnable && ! $thread->exists) {
                    continue;
                }

                $wasEnabled = $thread->exists && $thread->is_enabled;

                $thread->fill([
                    'type' => ChatThreadType::Group,
                    'title' => GroupChatAudienceRules::title($group, $audience),
                    'is_enabled' => $shouldEnable,
                    'created_by_user_id' => $thread->created_by_user_id ?: $actorUserId,
                ]);
                $thread->save();

                if ($shouldEnable !== $wasEnabled) {
                    audit()->log(
                        event: $shouldEnable ? 'group_chat.enabled' : 'group_chat.disabled',
                        userId: $actorUserId,
                        auditable: $group,
                        newValues: [
                            'chat_thread_id' => $thread->id,
                            'audience' => $audience->value,
                        ],
                        metadata: [
                            'source' => 'group_chat_threads_update',
                        ],
                    );
                }
            }

            $this->syncGroupChatMembersAction->execute($group, false);

            return $group->chatThreads()
                ->with(['group', 'members.profile', 'latestMessage.user.profile'])
                ->orderBy('audience')
                ->get();
        });
    }
}
