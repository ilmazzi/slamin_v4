<?php

namespace Domain\Moderation\Actions;

use Domain\Event\Models\Event;
use Domain\Moderation\Models\ModerationDecision;
use Illuminate\Support\Collection;

class ListEventModerationHistoryAction
{
    public function execute(Event $event): Collection
    {
        return ModerationDecision::query()
            ->with('moderator.profile')
            ->where('moderatable_type', $event->getMorphClass())
            ->where('moderatable_id', $event->id)
            ->orderByDesc('created_at')
            ->get();
    }
}
