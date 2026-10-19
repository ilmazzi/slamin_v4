<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\RemoveGroupMemberFromChatThreadAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveGroupMemberAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly RemoveGroupMemberFromChatThreadAction $removeGroupMemberFromChatThreadAction,
    ) {}

    public function execute(Group $group, GroupMember $member, int $actorUserId): GroupMember
    {
        return DB::transaction(function () use ($group, $member, $actorUserId) {
            if ($member->group_id !== $group->id) {
                abort(404);
            }

            $actorMembership = $group->members()
                ->where('user_id', $actorUserId)
                ->where('status', GroupMemberStatus::Active)
                ->firstOrFail();

            if ($member->role === GroupMemberRole::Admin && $actorMembership->role !== GroupMemberRole::Admin) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.cannot_manage_admin')],
                ]);
            }

            if ($member->role === GroupMemberRole::Admin && $member->status === GroupMemberStatus::Active && $group->activeAdminsCount() <= 1) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.last_admin')],
                ]);
            }

            $member->update([
                'status' => GroupMemberStatus::Removed,
                'handled_by_user_id' => $actorUserId,
                'handled_at' => now(),
            ]);

            audit()->log(
                event: 'group.member_removed',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_member_remove',
                ],
            );

            if ($member->user_id !== $actorUserId) {
                $this->createNotificationAction->execute(
                    userId: $member->user_id,
                    type: 'group_member_removed',
                    title: fn () => __('group.notifications.member_removed_title'),
                    message: fn () => __('group.notifications.member_removed_message', [
                        'group' => $group->name,
                    ]),
                    data: [
                        'group_id' => $group->id,
                        'group_slug' => $group->slug,
                    ],
                );
            }

            $this->removeGroupMemberFromChatThreadAction->execute($group, $member->user_id);

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }
}
