<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\TranslationCommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListTranslationCommentsAction;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListTranslationCommentsController extends Controller
{
    public function __construct(
        private readonly ListTranslationCommentsAction $action,
    ) {}

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless($job->involves((int) $request->user()->id), 403);

        return ApiResponse::success([
            'comments' => TranslationCommentResource::collection($this->action->execute($job)),
        ]);
    }
}
