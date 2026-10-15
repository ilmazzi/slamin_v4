<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\AddGroupMemberToChatThreadAction;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveGroupRequestAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly AddGroupMemberToChatThreadAction $addGroupMemberToChatThreadAction,
    ) {}

    public function execute(Group $group, GroupMember $member, int $actorUserId): GroupMember
    {
        return DB::transaction(function () use ($group, $member, $actorUserId) {
            if ($member->group_id !== $group->id || $member->status !== GroupMemberStatus::Pending) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.request_not_pending')],
                ]);
            }

            $member->update([
                'status' => GroupMemberStatus::Active,
                'joined_at' => now(),
                'handled_by_user_id' => $actorUserId,
                'handled_at' => now(),
            ]);

            audit()->log(
                event: 'group.join_approved',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_request_approve',
                ],
            );

            $this->createNotificationAction->execute(
                userId: $member->user_id,
                type: 'group_join_approved',
                title: fn () => __('group.notifications.join_approved_title'),
                message: fn () => __('group.notifications.join_approved_message', [
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                ],
            );

            $this->addGroupMemberToChatThreadAction->execute($group, $member->user_id, $actorUserId);

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }
}
