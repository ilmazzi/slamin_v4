<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\StoreGigAnnouncementData;
use Domain\Gig\Enums\GigAnnouncementStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Support\Facades\Auth;

class StoreGigAnnouncementAction
{
    public function execute(Gig $gig, StoreGigAnnouncementData $data): GigAnnouncement
    {
        $announcement = $gig->announcements()->create([
            'author_user_id' => Auth::id(),
            'message' => $data->message,
            'status' => $data->status,
            'published_at' => $data->status === GigAnnouncementStatus::Published ? now() : null,
        ]);

        audit()->log(
            event: 'gig.announcement_created',
            userId: Auth::id(),
            auditable: $announcement,
            newValues: [
                'gig_id' => $gig->id,
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
                'published_at' => $announcement->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'gig_announcement_store',
                'giggable_type' => $gig->giggable_type,
                'giggable_id' => $gig->giggable_id,
            ],
        );

        return $announcement->fresh(['author.profile']);
    }
}
