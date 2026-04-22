<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateTranslationJobRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateTranslationJobContentAction;
use Domain\Gig\DTOs\UpdateTranslationJobData;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateTranslationJobController extends Controller
{
    public function __construct(
        private readonly UpdateTranslationJobContentAction $action,
    ) {
    }

    public function __invoke(UpdateTranslationJobRequest $request, TranslationJob $job): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $job), 403);

        $job = $this->action->execute($job, UpdateTranslationJobData::fromRequest($request));

        return ApiResponse::success([
            'translation_job' => [
                'id' => $job->id,
                'translated_content' => $job->translated_content,
                'deadline' => $job->deadline?->toISOString(),
                'status' => $job->status?->value,
            ],
        ]);
    }

    private function canAccess(int $userId, TranslationJob $job): bool
    {
        return $job->owner_user_id === $userId || $job->translator_user_id === $userId;
    }
}
