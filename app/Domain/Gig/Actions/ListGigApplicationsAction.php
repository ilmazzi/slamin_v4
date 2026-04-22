<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigApplicationsAction
{
    public function execute(Gig $gig): Collection
    {
        return $gig->applications()
            ->with('user.profile')
            ->latest()
            ->get();
    }
}
