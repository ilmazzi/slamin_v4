<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigNegotiationsAction
{
    public function execute(Gig $gig, int $userId): Collection
    {
        $query = $gig->negotiations()
            ->with(['application.user.profile', 'events.author.profile'])
            ->latest();

        $isOwner = (int) ($gig->giggable?->user_id ?? 0) === $userId;

        if (! $isOwner) {
            $query->where('translator_user_id', $userId);
        }

        return $query->get();
    }
}
