<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\TranslationCommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ResolveTranslationCommentAction;
use Domain\Gig\Models\TranslationComment;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolveTranslationCommentController extends Controller
{
    public function __construct(
        private readonly ResolveTranslationCommentAction $action,
    ) {}

    public function __invoke(Request $request, TranslationJob $job, TranslationComment $comment): JsonResponse
    {
        abort_unless($job->involves((int) $request->user()->id), 403);

        if ((int) $comment->translation_job_id !== (int) $job->id) {
            return ApiResponse::error(__('gig.translation_comment_not_found'), status: 404);
        }

        return ApiResponse::success([
            'comment' => new TranslationCommentResource($this->action->execute($comment)),
        ]);
    }
}
