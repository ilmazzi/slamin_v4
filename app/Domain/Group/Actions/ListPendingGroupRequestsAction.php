<?php

namespace Domain\Group\Actions;

use Domain\Group\Enums\GroupMemberStatus;
use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListPendingGroupRequestsAction
{
    public function execute(Group $group): Collection
    {
        return $group->members()
            ->with(['user.profile', 'handledBy.profile'])
            ->where(function ($query) {
                $query->where('status', GroupMemberStatus::Pending)
                    ->orWhere(function ($query) {
                        $query->where('status', GroupMemberStatus::Active)
                            ->whereIn('requested_role', ['editor', 'admin']);
                    });
            })
            ->orderBy('created_at')
            ->get();
    }
}
