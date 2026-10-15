<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListEventGigsAction
{
    public function execute(Event $event): Collection
    {
        return $event->gigs()
            ->withCount([
                'applications',
                'applications as pending_applications_count' => function (Builder $query): void {
                    $query->where('status', GigApplicationStatus::Pending->value);
                },
            ])
            ->orderBy('created_at')
            ->get();
    }
}
