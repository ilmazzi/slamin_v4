<?php

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\StoreCommentAction;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;

class StoreMediaCommentController extends Controller
{
    public function __construct(
        private readonly StoreCommentAction $action,
    ) {
    }

    public function __invoke(StoreCommentRequest $request, MediaItem $media): JsonResponse
    {
        $comment = $this->action->execute(
            commentable: $media,
            userId: $request->user()->id,
            content: $request->validated('content'),
            parentId: $request->validated('parent_id'),
        );

        return ApiResponse::success([
            'comment' => new CommentResource($comment),
        ]);
    }
}
