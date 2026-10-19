<?php

namespace Domain\Group\Policies;

use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Enums\GroupVisibility;
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

        if (! $user) {
            return false;
        }

        if ($group->visibility === GroupVisibility::Public || $group->is_listed) {
            return true;
        }

        return $group->hasActiveMember($user->id);
    }

    public function viewFeed(User $user, Group $group): bool
    {
        if (! $this->view($user, $group)) {
            return false;
        }

        if ($group->visibility === GroupVisibility::Public) {
            return true;
        }

        return $group->hasActiveMember($user->id);
    }

    public function create(User $user): bool
    {
        return $user->isPoetOrganizer();
    }

    public function update(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function follow(User $user, Group $group): bool
    {
        if (! $group->is_active) {
            return false;
        }

        if ($group->visibility === GroupVisibility::Private && ! $group->is_listed) {
            return false;
        }

        $member = $group->members()->where('user_id', $user->id)->first();

        return $member === null
            || ! in_array($member->status, [GroupMemberStatus::Active, GroupMemberStatus::Pending], true);
    }

    public function requestJoin(User $user, Group $group): bool
    {
        return $this->follow($user, $group);
    }

    public function requestRole(User $user, Group $group): bool
    {
        if (! $group->is_active) {
            return false;
        }

        $member = $group->members()->where('user_id', $user->id)->first();
        $isActiveMember = $member?->status === GroupMemberStatus::Active;

        if ($group->visibility === GroupVisibility::Private && ! $group->is_listed && ! $isActiveMember) {
            return false;
        }

        if ($isActiveMember && $member->role === GroupMemberRole::Admin) {
            return false;
        }

        return $member?->requested_role === null
            || ! in_array($member->status, [GroupMemberStatus::Active, GroupMemberStatus::Pending], true);
    }

    public function invite(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
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
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function approveRequests(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function manageRoles(User $user, Group $group, GroupMember $target): bool
    {
        if (! $group->hasActiveRole($user->id, [GroupMemberRole::Admin])) {
            return false;
        }

        return $target->role !== GroupMemberRole::Admin
            || $group->activeAdminsCount() > 1
            || $target->status !== GroupMemberStatus::Active;
    }

    public function publishAnnouncements(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [
            GroupMemberRole::Admin,
            GroupMemberRole::Editor,
        ]);
    }

    public function manageContentLinks(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [
            GroupMemberRole::Admin,
            GroupMemberRole::Editor,
        ]);
    }

    public function viewAffiliations(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [
            GroupMemberRole::Admin,
            GroupMemberRole::Editor,
        ]);
    }

    public function manageAffiliations(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function manageChatThreads(User $user, Group $group): bool
    {
        return $group->hasActiveRole($user->id, [GroupMemberRole::Admin]);
    }

    public function proposeContent(User $user, Group $group): bool
    {
        return $group->canReceiveProposalFrom($user->id);
    }
}
