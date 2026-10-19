<?php

namespace Domain\Chat\Actions;

use Domain\Chat\Models\ChatThread;
use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Group\Support\GroupChatAudienceRules;

class SyncGroupChatMembersAction
{
    public function execute(Group $group, bool $syncParents = true): void
    {
        $this->syncGroup($group);

        if (! $syncParents) {
            return;
        }

        $parentIds = GroupAffiliation::query()
            ->where('child_group_id', $group->id)
            ->where('status', GroupAffiliationStatus::Accepted)
            ->pluck('parent_group_id');

        if ($parentIds->isEmpty()) {
            return;
        }

        Group::query()
            ->whereIn('id', $parentIds)
            ->get()
            ->each(fn (Group $parent) => $this->execute($parent, false));
    }

    private function syncGroup(Group $group): void
    {
        $threads = ChatThread::query()
            ->where('group_id', $group->id)
            ->where('is_enabled', true)
            ->whereNotNull('audience')
            ->get();

        foreach ($threads as $thread) {
            $audience = $thread->audience instanceof GroupChatAudience
                ? $thread->audience
                : GroupChatAudience::tryFrom((string) $thread->audience);

            if (! $audience) {
                continue;
            }

            $desiredIds = GroupChatAudienceRules::userIds($group, $audience)->all();
            $currentIds = $thread->memberRecords()->pluck('user_id')->map(fn ($id) => (int) $id)->all();

            foreach (array_diff($desiredIds, $currentIds) as $userId) {
                $thread->memberRecords()->firstOrCreate(
                    ['user_id' => (int) $userId],
                    ['joined_at' => now()],
                );
            }

            $extraIds = array_diff($currentIds, $desiredIds);
            if ($extraIds !== []) {
                $thread->memberRecords()->whereIn('user_id', $extraIds)->delete();
            }
        }
    }
}
