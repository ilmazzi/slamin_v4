<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\StoreCommentAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class StoreGroupCommentController extends Controller
{
    public function __construct(
        private readonly StoreCommentAction $action,
    ) {}

    public function __invoke(StoreCommentRequest $request, Group $group): JsonResponse
    {
        $this->authorize('comment', $group);

        $comment = $this->action->execute(
            commentable: $group,
            userId: $request->user()->id,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
