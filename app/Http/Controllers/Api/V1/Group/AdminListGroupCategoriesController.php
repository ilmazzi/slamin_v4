<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\AdminGroupCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Queries\AdminListGroupCategoryQuery;
use Illuminate\Http\JsonResponse;

class AdminListGroupCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListGroupCategoryQuery $adminListGroupCategoryQuery,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminGroupCategoryResource::collection($this->adminListGroupCategoryQuery->execute()),
        ]);
    }
}
