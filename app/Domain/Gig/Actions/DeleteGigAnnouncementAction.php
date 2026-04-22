<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Support\Facades\Auth;

class DeleteGigAnnouncementAction
{
    public function execute(GigAnnouncement $announcement): void
    {
        audit()->log(
            event: 'gig.announcement_deleted',
            userId: Auth::id(),
            auditable: $announcement,
            oldValues: [
                'gig_id' => $announcement->gig_id,
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
            ],
            metadata: [
                'source' => 'gig_announcement_delete',
            ],
        );

        $announcement->delete();
    }
}
