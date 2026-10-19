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

class RequestGroupRoleAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly AddGroupMemberToChatThreadAction $addGroupMemberToChatThreadAction,
    ) {}

    public function execute(Group $group, int $userId, GroupMemberRole $requestedRole): GroupMember
    {
        if (! $requestedRole->isStaff()) {
            throw ValidationException::withMessages([
                'requested_role' => [__('group.errors.invalid_requested_role')],
            ]);
        }

        return DB::transaction(function () use ($group, $userId, $requestedRole) {
            $member = $group->members()->where('user_id', $userId)->first();

            if ($member?->status === GroupMemberStatus::Active
                && $member->role
                && $member->role->rank() >= $requestedRole->rank()) {
                throw ValidationException::withMessages([
                    'requested_role' => [__('group.errors.already_has_role')],
                ]);
            }

            if ($member?->requested_role !== null
                && in_array($member->status, [GroupMemberStatus::Pending, GroupMemberStatus::Active], true)) {
                throw ValidationException::withMessages([
                    'requested_role' => [__('group.errors.already_requested')],
                ]);
            }

            $isPublic = $group->visibility === GroupVisibility::Public;
            $wasActiveFollower = $member?->status === GroupMemberStatus::Active;

            $member ??= new GroupMember([
                'group_id' => $group->id,
                'user_id' => $userId,
            ]);

            if ($wasActiveFollower) {
                $member->requested_role = $requestedRole;
                $member->save();
            } else {
                $member->role = GroupMemberRole::Follower;
                $member->requested_role = $requestedRole;
                $member->status = $isPublic ? GroupMemberStatus::Active : GroupMemberStatus::Pending;
                $member->joined_at = $isPublic ? now() : null;
                $member->handled_by_user_id = $isPublic ? $userId : null;
                $member->handled_at = $isPublic ? now() : null;
                $member->save();

                if ($isPublic) {
                    $this->addGroupMemberToChatThreadAction->execute($group, $userId, $userId);
                }
            }

            audit()->log(
                event: 'group.role_requested',
                userId: $userId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'requested_role' => $requestedRole->value,
                    'status' => $member->status?->value,
                ],
                metadata: [
                    'source' => 'group_role_request',
                ],
            );

            $this->notifyAdmins($group, $userId, $requestedRole);

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }

    private function notifyAdmins(Group $group, int $userId, GroupMemberRole $requestedRole): void
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
                type: 'group_role_requested',
                title: fn () => __('group.notifications.role_requested_title'),
                message: fn () => __('group.notifications.role_requested_message', [
                    'user' => $displayName,
                    'group' => $group->name,
                    'role' => __('group.roles.'.$requestedRole->value),
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'requested_user_id' => $userId,
                    'requested_role' => $requestedRole->value,
                ],
            );
        }
    }
}
