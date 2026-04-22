<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigApplicationStatus;
use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WithdrawGigApplicationAction
{
    public function execute(Gig $gig, GigApplication $application): GigApplication
    {
        if ($gig->status === GigStatus::Closed || $gig->status === GigStatus::Cancelled) {
            throw ValidationException::withMessages([
                'application' => [__('gig.validation.cannot_withdraw_from_closed_gig')],
            ]);
        }

        $oldStatus = $application->status;

        $application->status = GigApplicationStatus::Withdrawn;
        $application->withdrawn_at = now();
        $application->confirmed_at = null;
        $application->rejected_at = null;
        $application->save();

        audit()->log(
            event: 'gig.application_withdrawn',
            userId: Auth::id(),
            auditable: $application,
            oldValues: [
                'status' => $oldStatus?->value,
            ],
            newValues: [
                'status' => $application->status?->value,
            ],
            metadata: [
                'source' => 'gig_application_withdraw',
                'gig_id' => $gig->id,
            ],
        );

        return $application->fresh(['user.profile', 'gig']);
    }
}
