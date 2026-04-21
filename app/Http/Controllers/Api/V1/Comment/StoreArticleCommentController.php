<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Comment\Actions\StoreCommentAction;
use Illuminate\Http\JsonResponse;

class StoreArticleCommentController extends Controller
{
    public function __construct(
        private readonly StoreCommentAction $action,
    ) {
    }

    public function __invoke(StoreCommentRequest $request, Article $article): JsonResponse
    {
        $comment = $this->action->execute(
            commentable: $article,
            userId: $request->user()->id,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
