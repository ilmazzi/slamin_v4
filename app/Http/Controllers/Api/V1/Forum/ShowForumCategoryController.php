<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Forum\ForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Queries\ShowForumCategoryQuery;
use Illuminate\Http\JsonResponse;

class ShowForumCategoryController extends Controller
{
    public function __construct(
        private readonly ShowForumCategoryQuery $query,
    ) {}

    public function __invoke(string $categorySlug): JsonResponse
    {
        return ApiResponse::success([
            'category' => new ForumCategoryResource($this->query->execute($categorySlug)),
        ]);
    }
}
