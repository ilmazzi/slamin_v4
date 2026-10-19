<?php

namespace Domain\Group\Support;

use Domain\Group\Enums\GroupAffiliationStatus;
use Domain\Group\Enums\GroupChatAudience;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Domain\Group\Models\GroupMember;
use Illuminate\Support\Collection;

class GroupChatAudienceRules
{
    public static function userIds(Group $group, GroupChatAudience $audience): Collection
    {
        $groupAdminIds = static::activeRoleIds($group->id, [GroupMemberRole::Admin]);

        return match ($audience) {
            GroupChatAudience::Admin => $groupAdminIds,
            GroupChatAudience::Editor => static::activeRoleIds($group->id, [
                GroupMemberRole::Admin,
                GroupMemberRole::Editor,
            ]),
            GroupChatAudience::Public => static::activeRoleIds($group->id, [
                GroupMemberRole::Admin,
                GroupMemberRole::Editor,
                GroupMemberRole::Follower,
            ]),
            GroupChatAudience::Affiliates => $groupAdminIds
                ->merge(static::childAdminIds($group->id))
                ->unique()
                ->values(),
        };
    }

    public static function title(Group $group, GroupChatAudience $audience): string
    {
        return trim($group->name.' '.__($audience->labelKey()));
    }

    private static function activeRoleIds(int $groupId, array $roles): Collection
    {
        return GroupMember::query()
            ->where('group_id', $groupId)
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', array_map(
                fn (GroupMemberRole $role) => $role->value,
                $roles,
            ))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private static function childAdminIds(int $parentGroupId): Collection
    {
        $childIds = GroupAffiliation::query()
            ->where('parent_group_id', $parentGroupId)
            ->where('status', GroupAffiliationStatus::Accepted)
            ->pluck('child_group_id');

        if ($childIds->isEmpty()) {
            return collect();
        }

        return static::activeRoleIdsAcross($childIds->all(), [GroupMemberRole::Admin]);
    }

    private static function activeRoleIdsAcross(array $groupIds, array $roles): Collection
    {
        return GroupMember::query()
            ->whereIn('group_id', $groupIds)
            ->where('status', GroupMemberStatus::Active)
            ->whereIn('role', array_map(
                fn (GroupMemberRole $role) => $role->value,
                $roles,
            ))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
