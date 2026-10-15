<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Gig\Models\TranslationJob;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RequestTranslationChangesAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(TranslationJob $job): TranslationJob
    {
        if ($job->status !== TranslationJobStatus::Submitted) {
            throw ValidationException::withMessages([
                'translation_job' => [__('gig.validation.translation_job_invalid_status')],
            ]);
        }

        $job->status = TranslationJobStatus::ChangesRequested;
        $job->save();

        audit()->log(
            event: 'translation_job.changes_requested',
            userId: Auth::id(),
            auditable: $job,
            newValues: [
                'status' => $job->status?->value,
            ],
            metadata: [
                'source' => 'translation_job_request_changes',
            ],
        );

        $this->createNotificationAction->execute(
            userId: $job->translator_user_id,
            type: 'translation_job.changes_requested',
            title: __('gig.notifications.translation_changes_requested_title'),
            message: __('gig.notifications.translation_changes_requested_message'),
            data: [
                'translation_job_id' => $job->id,
                'gig_id' => $job->gig_id,
            ],
        );

        return $job->fresh();
    }
}
