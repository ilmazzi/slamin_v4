<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\TranslationJobResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\RequestTranslationChangesAction;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestTranslationChangesController extends Controller
{
    public function __construct(
        private readonly RequestTranslationChangesAction $action,
    ) {}

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless((int) $job->owner_user_id === (int) $request->user()->id, 403);

        return ApiResponse::success([
            'translation_job' => new TranslationJobResource($this->action->execute($job)),
        ]);
    }
}
