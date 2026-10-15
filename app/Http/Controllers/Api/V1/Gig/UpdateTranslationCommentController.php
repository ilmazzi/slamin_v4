<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateTranslationCommentRequest;
use App\Http\Resources\Api\V1\Gig\TranslationCommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateTranslationCommentAction;
use Domain\Gig\DTOs\UpdateTranslationCommentData;
use Domain\Gig\Models\TranslationComment;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;

class UpdateTranslationCommentController extends Controller
{
    public function __construct(
        private readonly UpdateTranslationCommentAction $action,
    ) {}

    public function __invoke(UpdateTranslationCommentRequest $request, TranslationJob $job, TranslationComment $comment): JsonResponse
    {
        if ((int) $comment->translation_job_id !== (int) $job->id) {
            return ApiResponse::error(__('gig.translation_comment_not_found'), status: 404);
        }

        abort_unless(
            (int) $comment->author_user_id === (int) $request->user()->id && $job->involves((int) $request->user()->id),
            403,
        );

        $comment = $this->action->execute($comment, UpdateTranslationCommentData::fromRequest($request));

        return ApiResponse::success([
            'comment' => new TranslationCommentResource($comment),
        ]);
    }
}
