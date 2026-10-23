<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\ListForumPostRequest;
use App\Http\Resources\Api\V1\Forum\ForumPostResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Queries\ListForumPostQuery;
use Illuminate\Http\JsonResponse;

class ListForumPostsController extends Controller
{
    public function __construct(
        private readonly ListForumPostQuery $query,
    ) {}

    public function __invoke(ListForumPostRequest $request): JsonResponse
    {
        $posts = $this->query->execute(
            categorySlug: $request->validated('category'),
            sort: $request->validated('sort', 'new') ?: 'new',
            perPage: (int) ($request->validated('per_page') ?: 20),
            viewerId: auth('sanctum')->id() ? (int) auth('sanctum')->id() : null,
        );

        return ApiResponse::success(
            data: ForumPostResource::collection($posts->items()),
            meta: [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        );
    }
}
