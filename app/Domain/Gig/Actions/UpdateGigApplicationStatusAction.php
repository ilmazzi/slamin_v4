<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateGigApplicationStatusData;
use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;

class UpdateGigApplicationStatusAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(Gig $gig, GigApplication $application, UpdateGigApplicationStatusData $data): GigApplication
    {
        $oldStatus = $application->status;
        $newStatus = $data->status;
        $now = now();

        $application->status = $newStatus;
        $application->reviewed_by_user_id = Auth::id();
        $application->reviewed_at = $now;

        if ($newStatus === GigApplicationStatus::Confirmed) {
            $application->confirmed_at = $now;
            $application->rejected_at = null;
            $application->withdrawn_at = null;
        }

        if ($newStatus === GigApplicationStatus::Rejected) {
            $application->rejected_at = $now;
            $application->confirmed_at = null;
        }

        if ($newStatus === GigApplicationStatus::Accepted) {
            $application->confirmed_at = null;
            $application->rejected_at = null;
        }

        if ($newStatus === GigApplicationStatus::Pending) {
            $application->confirmed_at = null;
            $application->rejected_at = null;
            $application->withdrawn_at = null;
        }

        $application->save();

        $this->refreshGigCoverage($gig);

        audit()->log(
            event: $newStatus === GigApplicationStatus::Confirmed ? 'gig.application_confirmed' : 'gig.application_updated',
            userId: Auth::id(),
            auditable: $application,
            oldValues: [
                'status' => $oldStatus?->value,
            ],
            newValues: [
                'status' => $application->status?->value,
                'reviewed_by_user_id' => $application->reviewed_by_user_id,
            ],
            metadata: [
                'source' => 'gig_application_status_update',
                'gig_id' => $gig->id,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $application->user_id,
            type: 'gig.application_updated',
            title: __('gig.notifications.application_updated_title'),
            message: __('gig.notifications.application_status_message', [
                'status' => __('gig.application_statuses.' . $application->status->value),
            ]),
            data: [
                'gig_id' => $gig->id,
                'application_id' => $application->id,
                'status' => $application->status->value,
            ],
        );

        return $application->fresh(['user.profile', 'gig']);
    }

    private function refreshGigCoverage(Gig $gig): void
    {
        $confirmedCount = $gig->applications()
            ->where('status', GigApplicationStatus::Confirmed->value)
            ->count();

        $gig->filled_quantity = $confirmedCount;

        if ($confirmedCount >= $gig->quantity) {
            $gig->status = GigStatus::Filled;
            $gig->filled_at = $gig->filled_at ?? now();
        } elseif ($gig->status === GigStatus::Filled) {
            $gig->status = GigStatus::Open;
            $gig->filled_at = null;
        }

        $gig->updated_by_user_id = Auth::id();
        $gig->save();
    }
}
