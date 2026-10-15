<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigAnnouncementStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListGigAnnouncementsAction
{
    public function execute(Gig $gig, bool $includeUnpublished = false): Collection
    {
        return $gig->announcements()
            ->with('author.profile')
            ->when(! $includeUnpublished, fn ($query) => $query->where('status', GigAnnouncementStatus::Published->value))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
