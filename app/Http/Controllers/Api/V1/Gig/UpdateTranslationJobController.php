<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateTranslationJobRequest;
use App\Http\Resources\Api\V1\Gig\TranslationJobResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateTranslationJobContentAction;
use Domain\Gig\DTOs\UpdateTranslationJobData;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;

class UpdateTranslationJobController extends Controller
{
    public function __construct(
        private readonly UpdateTranslationJobContentAction $action,
    ) {}

    public function __invoke(UpdateTranslationJobRequest $request, TranslationJob $job): JsonResponse
    {
        abort_unless($job->involves((int) $request->user()->id), 403);

        $job = $this->action->execute($job, UpdateTranslationJobData::fromRequest($request));

        return ApiResponse::success([
            'translation_job' => new TranslationJobResource($job),
        ]);
    }
}
