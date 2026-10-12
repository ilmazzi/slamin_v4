<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\StoreCommentAction;
use Domain\Event\Models\Event;
use Illuminate\Http\JsonResponse;

class StoreEventCommentController extends Controller
{
    public function __construct(
        private readonly StoreCommentAction $action,
    ) {}

    public function __invoke(StoreCommentRequest $request, Event $event): JsonResponse
    {
        $this->authorize('comment', $event);

        $comment = $this->action->execute(
            commentable: $event,
            userId: $request->user()->id,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
