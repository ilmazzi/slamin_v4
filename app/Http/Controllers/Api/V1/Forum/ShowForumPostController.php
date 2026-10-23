<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Forum\ForumPostResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Queries\ShowForumPostQuery;
use Illuminate\Http\JsonResponse;

class ShowForumPostController extends Controller
{
    public function __construct(
        private readonly ShowForumPostQuery $query,
    ) {}

    public function __invoke(string $forumPost): JsonResponse
    {
        $post = $this->query->byPublicId(
            $forumPost,
            auth('sanctum')->id() ? (int) auth('sanctum')->id() : null,
        );

        return ApiResponse::success([
            'post' => new ForumPostResource($post),
        ]);
    }
}
