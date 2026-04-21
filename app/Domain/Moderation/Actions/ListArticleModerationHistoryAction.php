<?php

namespace Domain\Moderation\Actions;

use Domain\Article\Models\Article;
use Domain\Moderation\Models\ModerationDecision;
use Illuminate\Support\Collection;

class ListArticleModerationHistoryAction
{
    public function execute(Article $article): Collection
    {
        return ModerationDecision::query()
            ->with('moderator.profile')
            ->where('moderatable_type', $article->getMorphClass())
            ->where('moderatable_id', $article->id)
            ->orderByDesc('created_at')
            ->get();
    }
}
