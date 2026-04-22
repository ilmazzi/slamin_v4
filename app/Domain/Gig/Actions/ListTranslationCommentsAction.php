<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\TranslationJob;
use Illuminate\Database\Eloquent\Collection;

class ListTranslationCommentsAction
{
    public function execute(TranslationJob $job): Collection
    {
        return $job->comments()
            ->with('author.profile')
            ->latest()
            ->get();
    }
}
