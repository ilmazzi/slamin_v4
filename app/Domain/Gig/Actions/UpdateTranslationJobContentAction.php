<?php

namespace Domain\Gig\Actions;

use Domain\Gig\DTOs\UpdateTranslationJobData;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Support\Facades\Auth;

class UpdateTranslationJobContentAction
{
    public function execute(TranslationJob $job, UpdateTranslationJobData $data): TranslationJob
    {
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
