<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectGroupRequestAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(Group $group, GroupMember $member, int $actorUserId): GroupMember
    {
        return DB::transaction(function () use ($group, $member, $actorUserId) {
            $isPendingEntry = $member->status === GroupMemberStatus::Pending;
            $isRoleUpgrade = $member->status === GroupMemberStatus::Active && $member->requested_role !== null;

            if ($member->group_id !== $group->id || (! $isPendingEntry && ! $isRoleUpgrade)) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.request_not_pending')],
                ]);
            }

            $member->update([
                'requested_role' => null,
                'status' => $isPendingEntry ? GroupMemberStatus::Rejected : $member->status,
                'handled_by_user_id' => $actorUserId,
                'handled_at' => now(),
            ]);

            audit()->log(
                event: 'group.join_rejected',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_request_reject',
                ],
            );

            $this->createNotificationAction->execute(
                userId: $member->user_id,
                type: 'group_join_rejected',
                title: fn () => __('group.notifications.join_rejected_title'),
                message: fn () => __('group.notifications.join_rejected_message', [
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                ],
            );

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }
}
