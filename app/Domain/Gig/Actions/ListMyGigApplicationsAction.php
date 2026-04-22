<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\GigApplication;
use Illuminate\Database\Eloquent\Collection;

class ListMyGigApplicationsAction
{
    public function execute(int $userId): Collection
    {
        return GigApplication::query()
            ->with(['gig.giggable'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
