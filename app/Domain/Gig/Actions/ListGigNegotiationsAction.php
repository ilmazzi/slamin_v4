<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigNegotiationsAction
{
    public function execute(Gig $gig): Collection
    {
        return $gig->negotiations()
            ->with(['application.user.profile', 'events.author.profile'])
            ->latest()
            ->get();
    }
}
