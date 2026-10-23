<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\StoreCommentAction;
use Domain\Forum\Models\ForumPost;
use Illuminate\Http\JsonResponse;

class StoreForumPostCommentController extends Controller
{
    public function __construct(
        private readonly StoreCommentAction $action,
    ) {}

    public function __invoke(StoreCommentRequest $request, ForumPost $forumPost): JsonResponse
    {
        $comment = $this->action->execute(
            commentable: $forumPost->load('category'),
            userId: $request->user()->id,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
