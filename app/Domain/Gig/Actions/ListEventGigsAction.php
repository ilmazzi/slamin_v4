<?php

namespace Domain\Gig\Actions;

use Domain\Event\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class ListEventGigsAction
{
    public function execute(Event $event): Collection
    {
        return $event->gigs()
            ->orderBy('created_at')
            ->get();
    }
}
