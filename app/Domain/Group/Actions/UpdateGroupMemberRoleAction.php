<?php

namespace Domain\Group\Actions;

use Domain\Chat\Actions\SyncGroupChatMembersAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateGroupMemberRoleAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly SyncGroupChatMembersAction $syncGroupChatMembersAction,
    ) {}

    public function execute(Group $group, GroupMember $member, GroupMemberRole $newRole, int $actorUserId): GroupMember
    {
        return DB::transaction(function () use ($group, $member, $newRole, $actorUserId) {
            if ($member->group_id !== $group->id || $member->status !== GroupMemberStatus::Active) {
                throw ValidationException::withMessages([
                    'group' => [__('group.errors.membership_not_active')],
                ]);
            }

            $actorMembership = $group->members()
                ->where('user_id', $actorUserId)
                ->where('status', GroupMemberStatus::Active)
                ->firstOrFail();

            if ($newRole === GroupMemberRole::Admin && $actorMembership->role !== GroupMemberRole::Admin) {
                throw ValidationException::withMessages([
                    'role' => [__('group.errors.cannot_promote_to_admin')],
                ]);
            }

            if ($member->role === GroupMemberRole::Admin && $actorMembership->role !== GroupMemberRole::Admin) {
                throw ValidationException::withMessages([
                    'role' => [__('group.errors.cannot_manage_admin')],
                ]);
            }

            if ($member->role === GroupMemberRole::Admin && $newRole !== GroupMemberRole::Admin && $group->activeAdminsCount() <= 1) {
                throw ValidationException::withMessages([
                    'role' => [__('group.errors.last_admin')],
                ]);
            }

            $member->update([
                'role' => $newRole,
                'handled_by_user_id' => $actorUserId,
                'handled_at' => now(),
            ]);

            audit()->log(
                event: 'group.member_role_updated',
                userId: $actorUserId,
                auditable: $group,
                newValues: [
                    'membership_id' => $member->id,
                    'role' => $member->role?->value,
                ],
                metadata: [
                    'source' => 'group_member_role_update',
                ],
            );

            $this->syncGroupChatMembersAction->execute($group);

            $this->createNotificationAction->execute(
                userId: $member->user_id,
                type: 'group_member_role_updated',
                title: fn () => __('group.notifications.member_role_updated_title'),
                message: fn () => __('group.notifications.member_role_updated_message', [
                    'group' => $group->name,
                    'role' => __("group.roles.{$member->role?->value}"),
                ]),
                data: [
                    'group_id' => $group->id,
                    'group_slug' => $group->slug,
                    'role' => $member->role?->value,
                ],
            );

            return $member->fresh(['user.profile', 'handledBy.profile']);
        });
    }
}
