<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\RemoveGroupMemberFromChatThreadAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveGroupAction
{
    public function __construct(
        private readonly RemoveGroupMemberFromChatThreadAction $removeGroupMemberFromChatThreadAction,
    ) {
    }

    public function execute(Group $group, int $userId): void
    {
        DB::transaction(function () use ($group, $userId) {
            $member = $group->members()
                ->where('user_id', $userId)
                ->where('status', GroupMemberStatus::Active)
                ->firstOrFail();

            if ($member->role === GroupMemberRole::Admin && $group->activeAdminsCount() <= 1) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.last_admin')],
                ]);
            }

            $member->update([
                'status' => GroupMemberStatus::Left,
                'handled_by_user_id' => $userId,
                'handled_at' => now(),
            ]);

            audit()->log(
                event: 'group.left',
                userId: $userId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_leave',
                ],
            );

            $this->removeGroupMemberFromChatThreadAction->execute($group, $userId);
        });
    }
}
