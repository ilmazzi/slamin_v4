<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Event\AdminEventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Queries\AdminListEventCategoryQuery;
use Illuminate\Http\JsonResponse;

class AdminListEventCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListEventCategoryQuery $adminListEventCategoryQuery,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminEventCategoryResource::collection($this->adminListEventCategoryQuery->execute()),
        ]);
    }
}
