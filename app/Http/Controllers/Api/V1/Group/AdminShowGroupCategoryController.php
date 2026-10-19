<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\AdminGroupCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Models\GroupCategory;
use Illuminate\Http\JsonResponse;

class AdminShowGroupCategoryController extends Controller
{
    public function __invoke(GroupCategory $groupCategory): JsonResponse
    {
        return ApiResponse::success([
            'category' => new AdminGroupCategoryResource($groupCategory->load('translations')),
        ]);
    }
}
