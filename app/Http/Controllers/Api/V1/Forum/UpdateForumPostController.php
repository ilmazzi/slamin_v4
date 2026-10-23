<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\UpdateForumPostRequest;
use App\Http\Resources\Api\V1\Forum\ForumPostResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\UpdateForumPostAction;
use Domain\Forum\Models\ForumPost;
use Illuminate\Http\JsonResponse;

class UpdateForumPostController extends Controller
{
    public function __construct(
        private readonly UpdateForumPostAction $action,
    ) {}

    public function __invoke(UpdateForumPostRequest $request, ForumPost $forumPost): JsonResponse
    {
        $this->authorize('update', $forumPost);

        $post = $this->action->execute($forumPost, $request->validated(), $request->user());

        return ApiResponse::success([
            'post' => new ForumPostResource($post),
        ]);
    }
}
