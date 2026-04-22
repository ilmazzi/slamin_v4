<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ShowTranslationJobAction;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowTranslationJobController extends Controller
{
    public function __construct(
        private readonly ShowTranslationJobAction $action,
    ) {
    }

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $job), 403);

        $job = $this->action->execute($job);

        return ApiResponse::success([
            'translation_job' => [
                'id' => $job->id,
                'gig_id' => $job->gig_id,
                'poem_id' => $job->poem_id,
                'source_language' => $job->source_language,
                'target_language' => $job->target_language,
                'agreed_price' => $job->agreed_price,
                'currency' => $job->currency,
                'deadline' => $job->deadline?->toISOString(),
                'translated_content' => $job->translated_content,
                'status' => $job->status?->value,
                'submitted_at' => $job->submitted_at?->toISOString(),
                'approved_at' => $job->approved_at?->toISOString(),
                'comments_count' => $job->comments->count(),
            ],
        ]);
    }

    private function canAccess(int $userId, TranslationJob $job): bool
    {
        return $job->owner_user_id === $userId || $job->translator_user_id === $userId;
    }
}
