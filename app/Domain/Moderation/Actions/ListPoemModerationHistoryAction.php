<?php

namespace Domain\Moderation\Actions;

use Domain\Moderation\Models\ModerationDecision;
use Domain\Poetry\Models\Poem;
use Illuminate\Support\Collection;

class ListPoemModerationHistoryAction
{
    public function execute(Poem $poem): Collection
    {
        return ModerationDecision::query()
            ->with('moderator.profile')
            ->where('moderatable_type', $poem->getMorphClass())
            ->where('moderatable_id', $poem->id)
            ->orderByDesc('created_at')
            ->get();
    }
}
