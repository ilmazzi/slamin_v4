<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\GigStatus;
use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Gig\Models\TranslationJob;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;

class ApproveTranslationJobAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {
    }

    public function execute(TranslationJob $job): TranslationJob
    {
        $job->status = TranslationJobStatus::Approved;
        $job->approved_at = now();
        $job->save();

        $gig = $job->gig;
        $gig->status = GigStatus::Closed;
        $gig->closed_at = $gig->closed_at ?? now();
        $gig->save();

        audit()->log(
            event: 'translation_job.approved',
            userId: Auth::id(),
            auditable: $job,
            newValues: [
                'status' => $job->status?->value,
                'approved_at' => $job->approved_at?->toISOString(),
            ],
            metadata: [
                'source' => 'translation_job_approve',
                'gig_id' => $gig->id,
            ],
        );

        $this->createNotificationAction->execute(
            userId: $job->translator_user_id,
            type: 'translation_job.approved',
            title: __('gig.notifications.translation_approved_title'),
            message: __('gig.notifications.translation_approved_message'),
            data: [
                'translation_job_id' => $job->id,
                'gig_id' => $gig->id,
            ],
        );

        return $job->fresh();
    }
}
