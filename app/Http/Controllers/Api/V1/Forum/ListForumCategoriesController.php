<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Forum\ForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Queries\ListForumCategoryQuery;
use Illuminate\Http\JsonResponse;

class ListForumCategoriesController extends Controller
{
    public function __construct(
        private readonly ListForumCategoryQuery $query,
    ) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => ForumCategoryResource::collection($this->query->execute()),
        ]);
    }
}
