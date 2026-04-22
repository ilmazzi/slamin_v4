<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListTranslationCommentsAction;
use Domain\Gig\Models\TranslationComment;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListTranslationCommentsController extends Controller
{
    public function __construct(
        private readonly ListTranslationCommentsAction $action,
    ) {
    }

    public function __invoke(Request $request, TranslationJob $job): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $job), 403);

        $comments = $this->action->execute($job);

        return ApiResponse::success([
            'comments' => $comments->map(fn (TranslationComment $comment): array => [
                'id' => $comment->id,
                'comment_target' => $comment->comment_target?->value,
                'target_locator' => $comment->target_locator,
                'content' => $comment->content,
                'status' => $comment->status?->value,
                'resolved_at' => $comment->resolved_at?->toISOString(),
                'author_user_id' => $comment->author_user_id,
            ])->values()->all(),
        ]);
    }

    private function canAccess(int $userId, TranslationJob $job): bool
    {
        return $job->owner_user_id === $userId || $job->translator_user_id === $userId;
    }
}
