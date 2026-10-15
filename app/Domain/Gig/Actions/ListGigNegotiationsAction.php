<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigNegotiationsAction
{
    public function execute(Gig $gig, int $userId): Collection
    {
        $query = $gig->negotiations()
            ->with(['translator.profile', 'translationJob'])
            ->withCount('events')
            ->latest()
            ->latest('id');

        if (! $gig->isManagedBy($userId)) {
            $query->where('translator_user_id', $userId);
        }

        return $query->get();
    }
}
