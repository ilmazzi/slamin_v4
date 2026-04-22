<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateGigAnnouncementData;
use Domain\Gig\Enums\GigAnnouncementStatus;
use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Support\Facades\Auth;

class UpdateGigAnnouncementAction
{
    public function execute(GigAnnouncement $announcement, UpdateGigAnnouncementData $data): GigAnnouncement
    {
        $oldValues = [
            'message' => $announcement->message,
            'status' => $announcement->status?->value,
            'published_at' => $announcement->published_at?->toISOString(),
        ];

        if ($data->messageProvided) {
            $announcement->message = $data->message;
        }

        if ($data->status !== null) {
            $announcement->status = $data->status;

            if ($data->status === GigAnnouncementStatus::Published && $announcement->published_at === null) {
                $announcement->published_at = now();
            }

            if ($data->status === GigAnnouncementStatus::Draft) {
                $announcement->published_at = null;
            }
        }

        $announcement->save();

        audit()->log(
            event: 'gig.announcement_updated',
            userId: Auth::id(),
            auditable: $announcement,
            oldValues: $oldValues,
            newValues: [
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
                'published_at' => $announcement->published_at?->toISOString(),
            ],
            metadata: [
                'source' => 'gig_announcement_update',
                'gig_id' => $announcement->gig_id,
            ],
        );

        return $announcement->fresh(['author.profile']);
    }
}
