<?php

namespace Domain\Group\Policies;

use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Models\GroupAnnouncement;
use Domain\User\Models\User;

class GroupAnnouncementPolicy
{
    public function update(User $user, GroupAnnouncement $announcement): bool
    {
        return $announcement->group->hasActiveRole($user->id, [
            GroupMemberRole::Owner,
            GroupMemberRole::Admin,
            GroupMemberRole::Moderator,
        ]);
    }

    public function delete(User $user, GroupAnnouncement $announcement): bool
    {
        return $announcement->group->hasActiveRole($user->id, [
            GroupMemberRole::Owner,
            GroupMemberRole::Admin,
            GroupMemberRole::Moderator,
        ]);
    }
}
