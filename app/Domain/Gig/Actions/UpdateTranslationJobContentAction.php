<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateTranslationJobData;
use Domain\Gig\Enums\TranslationJobStatus;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateTranslationJobContentAction
{
    public function execute(TranslationJob $job, UpdateTranslationJobData $data): TranslationJob
    {
        $isTranslator = (int) Auth::id() === (int) $job->translator_user_id;
        $editableStatuses = [TranslationJobStatus::InProgress, TranslationJobStatus::ChangesRequested];

        if ($data->translatedContentProvided && (! $isTranslator || ! in_array($job->status, $editableStatuses, true))) {
            throw ValidationException::withMessages([
                'translated_content' => [__('gig.validation.translation_job_not_editable')],
            ]);
        }

        if ($data->deadlineProvided && ($isTranslator || in_array($job->status, [TranslationJobStatus::Approved, TranslationJobStatus::Cancelled], true))) {
            throw ValidationException::withMessages([
                'deadline' => [__('gig.validation.translation_job_not_editable')],
            ]);
        }

        $oldValues = [
            'translated_content' => $job->translated_content,
            'deadline' => $job->deadline?->toISOString(),
        ];

        if ($data->translatedContentProvided) {
            $job->translated_content = $data->translatedContent;
        }

        if ($data->deadlineProvided) {
            $job->deadline = $data->deadline;
        }

        $job->save();

        audit()->log(
            event: 'translation_job.updated',
            userId: Auth::id(),
            auditable: $job,
            oldValues: $oldValues,
            newValues: [
                'translated_content' => $job->translated_content,
                'deadline' => $job->deadline?->toISOString(),
            ],
            metadata: [
                'source' => 'translation_job_update',
            ],
        );

        return $job->fresh();
    }
}
