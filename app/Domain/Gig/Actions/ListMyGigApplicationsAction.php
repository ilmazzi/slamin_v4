<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\Models\GigApplication;
use Domain\Poetry\Models\Poem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ListMyGigApplicationsAction
{
    public function execute(int $userId): Collection
    {
        return GigApplication::query()
            ->with([
                'gig.giggable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Event::class => ['venueDetail', 'occurrences'],
                        Poem::class => [],
                    ]);
                },
                'negotiations.translationJob',
            ])
            ->whereHas('gig')
            ->where('user_id', $userId)
            ->latest()
            ->latest('id')
            ->get();
    }
}
