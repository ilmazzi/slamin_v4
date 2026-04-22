<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\SubmitTranslationJobAction;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmitTranslationJobController extends Controller
{
    public function __construct(
        private readonly SubmitTranslationJobAction $action,
    ) {
    }

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless((int) $job->translator_user_id === (int) $request->user()->id, 403);

        $job = $this->action->execute($job);

        return ApiResponse::success([
            'translation_job' => [
                'id' => $job->id,
                'status' => $job->status?->value,
                'submitted_at' => $job->submitted_at?->toISOString(),
            ],
        ]);
    }
}
