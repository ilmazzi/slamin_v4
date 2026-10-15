<?php

namespace Domain\Gig\Actions;

use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Gig\Models\TranslationJob;
use Domain\Notification\Actions\CreateNotificationAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SubmitTranslationJobAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
    ) {}

    public function execute(TranslationJob $job): TranslationJob
    {
        if (! in_array($job->status, [TranslationJobStatus::InProgress, TranslationJobStatus::ChangesRequested], true)) {
            throw ValidationException::withMessages([
                'translation_job' => [__('gig.validation.translation_job_invalid_status')],
            ]);
        }

        if (blank($job->translated_content)) {
            throw ValidationException::withMessages([
                'translated_content' => [__('gig.validation.translation_content_required_for_submit')],
            ]);
        }

        $job->status = TranslationJobStatus::Submitted;
        $job->submitted_at = now();
        $job->save();

        audit()->log(
            event: 'translation_job.submitted',
            userId: Auth::id(),
            auditable: $job,
            newValues: [
                'status' => $job->status?->value,
                'submitted_at' => $job->submitted_at?->toISOString(),
            ],
            metadata: [
                'source' => 'translation_job_submit',
            ],
        );

        $this->createNotificationAction->execute(
            userId: $job->owner_user_id,
            type: 'translation_job.submitted',
            title: fn () => __('gig.notifications.translation_submitted_title'),
            message: fn () => __('gig.notifications.translation_submitted_message'),
            data: [
                'translation_job_id' => $job->id,
                'gig_id' => $job->gig_id,
            ],
        );

        return $job->fresh();
    }
}
