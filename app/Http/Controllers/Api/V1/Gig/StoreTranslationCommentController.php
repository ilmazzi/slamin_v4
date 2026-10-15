<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreTranslationCommentRequest;
use App\Http\Resources\Api\V1\Gig\TranslationCommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreTranslationCommentAction;
use Domain\Gig\DTOs\StoreTranslationCommentData;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;

class StoreTranslationCommentController extends Controller
{
    public function __construct(
        private readonly StoreTranslationCommentAction $action,
    ) {}

    public function __invoke(StoreTranslationCommentRequest $request, TranslationJob $job): JsonResponse
    {
        abort_unless($job->involves((int) $request->user()->id), 403);

        $comment = $this->action->execute($job, StoreTranslationCommentData::fromRequest($request));

        return ApiResponse::success([
            'comment' => new TranslationCommentResource($comment),
        ], status: 201);
    }
}
