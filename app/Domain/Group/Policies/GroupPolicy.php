<?php

namespace Domain\Group\Policies;

use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Domain\User\Models\User;

class GroupPolicy
{
    public function view(?User $user, Group $group): bool
    {
        if (! $group->is_active) {
            return false;
        }

        if ($group->visibility->value === 'public_open') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $group->hasActiveMember($user->id);
    }

    public function create(User $user): bool
    {
        return $user->isPoetOrganizer();
    }

    public function update(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Owner, GroupMemberRole::Admin]);
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Owner]);
    }

    public function requestJoin(User $user, Group $group): bool
    {
        if (! $group->is_active) {
            return false;
        }

        $member = $group->members()->where('user_id', $user->id)->first();

        return $member === null || $member->status !== GroupMemberStatus::Active;
    }

    public function leave(User $user, Group $group): bool
    {
        return $group->hasActiveMember($user->id);
    }

    public function viewMembers(User $user, Group $group): bool
    {
        return $group->hasActiveMember($user->id);
    }

    public function viewRequests(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Owner, GroupMemberRole::Admin]);
    }

    public function approveRequests(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Owner, GroupMemberRole::Admin]);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Owner, GroupMemberRole::Admin]);
    }

    public function manageRoles(User $user, Group $group, GroupMember $target): bool
    {
        $actorMembership = $group->members()
            ->where('user_id', $user->id)
            ->where('status', GroupMemberStatus::Active)
            ->first();

        if (! $actorMembership instanceof GroupMember) {
            return false;
        }

        if ($actorMembership->role === GroupMemberRole::Owner) {
            return true;
        }

        if ($actorMembership->role === GroupMemberRole::Admin) {
            return in_array($target->role, [GroupMemberRole::Moderator, GroupMemberRole::Member], true);
        }

        return false;
    }

    public function publishAnnouncements(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [
            GroupMemberRole::Owner,
            GroupMemberRole::Admin,
            GroupMemberRole::Moderator,
        ]);
    }
}
