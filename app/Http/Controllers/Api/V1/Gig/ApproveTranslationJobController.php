<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ApproveTranslationJobAction;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApproveTranslationJobController extends Controller
{
    public function __construct(
        private readonly ApproveTranslationJobAction $action,
    ) {
    }

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless((int) $job->owner_user_id === (int) $request->user()->id, 403);

        $job = $this->action->execute($job);

        return ApiResponse::success([
            'translation_job' => [
                'id' => $job->id,
                'status' => $job->status?->value,
                'approved_at' => $job->approved_at?->toISOString(),
            ],
        ]);
    }
}
