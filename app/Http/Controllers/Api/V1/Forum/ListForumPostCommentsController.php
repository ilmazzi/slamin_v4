<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Comment\ListCommentsRequest;
use App\Http\Resources\Api\V1\Comment\CommentResource;
use App\Support\Responses\ApiResponse;
use Domain\Comment\Actions\ListCommentsAction;
use Domain\Forum\Models\ForumPost;
use Illuminate\Http\JsonResponse;

class ListForumPostCommentsController extends Controller
{
    public function __construct(
        private readonly ListCommentsAction $action,
    ) {}

    public function __invoke(ListCommentsRequest $request, ForumPost $forumPost): JsonResponse
    {
        $comments = $this->action->execute(
            $forumPost,
            (int) $request->validated('per_page', 10),
            auth('sanctum')->id() ? (int) auth('sanctum')->id() : null,
        );

        return ApiResponse::success(
            data: CommentResource::collection($comments->items()),
            meta: [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        );
    }
}
