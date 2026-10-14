<?php

namespace Domain\Group\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Group\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class ListGroupEventsAction
{
    public function execute(Group $group, bool $canViewPrivate = false): Collection
    {
        return $group->events()
            ->with(Event::RESOURCE_RELATIONS)
            ->when(! $canViewPrivate, fn ($query) => $query
                ->where('status', EventStatus::Published)
                ->where('is_public', true))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
