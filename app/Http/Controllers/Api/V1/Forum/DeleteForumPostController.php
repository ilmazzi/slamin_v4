<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\DeleteForumPostAction;
use Domain\Forum\Models\ForumPost;
use Illuminate\Http\JsonResponse;

class DeleteForumPostController extends Controller
{
    public function __construct(
        private readonly DeleteForumPostAction $action,
    ) {}

    public function __invoke(ForumPost $forumPost): JsonResponse
    {
        $this->authorize('delete', $forumPost);
        $this->action->execute($forumPost, request()->user()->id);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
