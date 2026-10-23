<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Forum\AdminForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Queries\AdminListForumCategoryQuery;
use Illuminate\Http\JsonResponse;

class AdminListForumCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListForumCategoryQuery $query,
    ) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminForumCategoryResource::collection($this->query->execute()),
        ]);
    }
}
