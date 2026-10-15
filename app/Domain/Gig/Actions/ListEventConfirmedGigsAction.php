<?php

namespace Domain\Gig\Actions;

use Domain\Event\Enums\EventStatus;
use Domain\Event\Models\Event;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

class ListEventConfirmedGigsAction
{
    /**
     * @return Collection<int, Gig>
     */
    public function execute(Event $event, ?int $viewerId): Collection
    {
        $isOwner = $viewerId !== null && (int) $event->user_id === $viewerId;
        $isPublic = $event->status === EventStatus::Published && (bool) $event->is_public;

        if (! $isOwner && ! $isPublic) {
            return new Collection;
        }

        return $event->gigs()
            ->whereNotIn('status', [GigStatus::Draft->value, GigStatus::Cancelled->value])
            ->whereHas('confirmedApplications')
            ->with('confirmedApplications.user.profile')
            ->orderBy('id')
            ->get();
    }
}
