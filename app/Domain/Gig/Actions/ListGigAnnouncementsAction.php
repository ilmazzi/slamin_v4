<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigAnnouncementsAction
{
    public function execute(Gig $gig): Collection
    {
        return $gig->announcements()
            ->with('author.profile')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
