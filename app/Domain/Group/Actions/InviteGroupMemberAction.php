<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\AddGroupMemberToChatThreadAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InviteGroupMemberAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly AddGroupMemberToChatThreadAction $addGroupMemberToChatThreadAction,
    ) {}

    public function execute(Group $group, int $actorUserId, int $userId, GroupMemberRole $role): GroupMember
    {
        if ($actorUserId === $userId) {
            throw ValidationException::withMessages([
                'user_id' => [__('group.errors.invite_self')],
            ]);
        }

        if (! User::query()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('group.errors.invite_user_missing')],
            ]);
        }

        return DB::transaction(function () use ($group, $actorUserId, $userId, $role) {
            $member = $group->members()->where('user_id', $userId)->first();

            if ($member?->status === GroupMemberStatus::Active
                && $member->role === GroupMemberRole::Admin
                && $role !== GroupMemberRole::Admin) {
                throw ValidationException::withMessages([
                    'role' => [__('group.errors.cannot_manage_admin')],
                ]);
            }

            if ($member?->status === GroupMemberStatus::Active && $member->role === $role) {
                throw ValidationException::withMessages([
                    'user_id' => [__('group.errors.already_member')],
                ]);
            }

            $member ??= new GroupMember([
                'group_id' => $group->id,
                'user_id' => $userId,
            ]);

            $member->role = $role;
            $member->requested_role = null;
            $member->status = GroupMemberStatus::Active;
            $member->joined_at = $member->joined_at ?? now();
            $member->handled_by_user_id = $actorUserId;
            $member->handled_at = now();
            $member->save();

            audit()->log(
                event: 'group.invited',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'invited_user_id' => $userId,
                    'role' => $role->value,
                ],
                metadata: [
                    'source' => 'group_invite',
                ],
            );

            $this->createNotificationAction->execute(
                userId: $userId,
                type: 'group_invited',
                title: fn () => __('group.notifications.invited_title'),
                message: fn () => __('group.notifications.invited_message', [
                    'group' => $group->name,
                    'role' => __('group.roles.'.$role->value),
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'role' => $role->value,
                ],
            );

            $this->addGroupMemberToChatThreadAction->execute($group, $userId, $actorUserId);

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }
}
