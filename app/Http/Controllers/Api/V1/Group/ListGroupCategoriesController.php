<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Queries\ListGroupCategoryQuery;
use Illuminate\Http\JsonResponse;

class ListGroupCategoriesController extends Controller
{
    public function __construct(
        private readonly ListGroupCategoryQuery $listGroupCategoryQuery,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => GroupCategoryResource::collection($this->listGroupCategoryQuery->execute()),
        ]);
    }
}
