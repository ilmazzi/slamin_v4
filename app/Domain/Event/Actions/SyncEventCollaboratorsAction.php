<?php

namespace Domain\Event\Actions;

use Domain\Event\Models\Event;

class SyncEventCollaboratorsAction
{
    /**
     * @param  array<int, int|string>  $userIds
     */
    public function execute(Event $event, array $userIds): void
    {
        $ownerId = (int) $event->user_id;
        $ids = collect($userIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== $ownerId)
            ->unique()
            ->values()
            ->all();

        $event->collaborators()->sync($ids);
    }
}
