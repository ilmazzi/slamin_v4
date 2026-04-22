<?php

namespace Domain\Group\Actions;

use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListGroupAnnouncementsAction
{
    public function execute(Group $group): Collection
    {
        return $group->announcements()
            ->with('author.profile')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
