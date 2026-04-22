<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreTranslationCommentRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreTranslationCommentAction;
use Domain\Gig\DTOs\StoreTranslationCommentData;
use Domain\Gig\Models\TranslationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreTranslationCommentController extends Controller
{
    public function __construct(
        private readonly StoreTranslationCommentAction $action,
    ) {
    }

    public function __invoke(StoreTranslationCommentRequest $request, TranslationJob $job): JsonResponse
    {
        abort_unless($this->canAccess($request->user()->id, $job), 403);

        $comment = $this->action->execute($job, StoreTranslationCommentData::fromRequest($request));

        return ApiResponse::success([
            'comment' => [
                'id' => $comment->id,
                'comment_target' => $comment->comment_target?->value,
                'target_locator' => $comment->target_locator,
                'content' => $comment->content,
                'status' => $comment->status?->value,
            ],
        ], status: 201);
    }

    private function canAccess(int $userId, TranslationJob $job): bool
    {
        return $job->owner_user_id === $userId || $job->translator_user_id === $userId;
    }
}
