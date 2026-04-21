<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\UpdateCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\UpdateCommentAction;
use Domain\Comment\Models\Comment;
use Illuminate\Http\JsonResponse;

class UpdateCommentController extends Controller
{
    public function __construct(
        private readonly UpdateCommentAction $action,
    ) {
    }

    public function __invoke(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment = $this->action->execute(
            comment: $comment,
            userId: $request->user()->id,
            content: $request->validated('content'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
