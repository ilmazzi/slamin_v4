<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\AddGroupMemberToChatThreadAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowGroupAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly AddGroupMemberToChatThreadAction $addGroupMemberToChatThreadAction,
    ) {}

    public function execute(Group $group, int $userId): GroupMember
    {
        return DB::transaction(function () use ($group, $userId) {
            $member = $group->members()->where('user_id', $userId)->first();

            if ($member && $member->status === GroupMemberStatus::Active) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.already_following')],
                ]);
            }

            if ($member && $member->status === GroupMemberStatus::Pending) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.already_requested')],
                ]);
            }

            $targetStatus = $group->visibility === GroupVisibility::Public
                ? GroupMemberStatus::Active
                : GroupMemberStatus::Pending;

            $member ??= new GroupMember([
                'group_id' => $group->id,
                'user_id' => $userId,
            ]);

            $member->role = GroupMemberRole::Follower;
            $member->requested_role = null;
            $member->status = $targetStatus;
            $member->joined_at = $targetStatus === GroupMemberStatus::Active ? now() : null;
            $member->handled_by_user_id = $targetStatus === GroupMemberStatus::Active ? $userId : null;
            $member->handled_at = $targetStatus === GroupMemberStatus::Active ? now() : null;
            $member->save();

            audit()->log(
                event: 'group.follow_requested',
                userId: $userId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_follow',
                ],
            );

            if ($targetStatus === GroupMemberStatus::Pending) {
                $this->notifyAdmins($group, $userId);
            } else {
                $this->addGroupMemberToChatThreadAction->execute($group, $userId, $userId);
            }

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }

    private function notifyAdmins(Group $group, int $userId): void
    {
        $actor = User::query()->with('profile')->find($userId);
        $displayName = $actor?->profile?->display_name
            ?? $actor?->profile?->nickname
            ?? $actor?->email
            ?? 'User';

        $adminIds = $group->members()
            ->where('status', GroupMemberStatus::Active)
            ->where('role', GroupMemberRole::Admin)
            ->pluck('user_id')
            ->unique();

        foreach ($adminIds as $adminId) {
            $this->createNotificationAction->execute(
                userId: (int) $adminId,
                type: 'group_follow_requested',
                title: fn () => __('group.notifications.follow_requested_title'),
                message: fn () => __('group.notifications.follow_requested_message', [
                    'user' => $displayName,
                    'group' => $group->name,
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'requested_user_id' => $userId,
                ],
            );
        }
    }
}
