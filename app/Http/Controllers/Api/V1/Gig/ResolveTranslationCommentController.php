<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
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
    ) {
    }

    public function __invoke(Request $request, TranslationJob $job, TranslationComment $comment): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $job), 403);

        if ((int) $comment->translation_job_id !== (int) $job->id) {
            return ApiResponse::error(__('gig.translation_comment_not_found'), status: 404);
        }

        $comment = $this->action->execute($comment);

        return ApiResponse::success([
            'comment' => [
                'id' => $comment->id,
                'status' => $comment->status?->value,
                'resolved_at' => $comment->resolved_at?->toISOString(),
            ],
        ]);
    }

    private function canAccess(int $userId, TranslationJob $job): bool
    {
        return $job->owner_user_id === $userId || $job->translator_user_id === $userId;
    }
}
