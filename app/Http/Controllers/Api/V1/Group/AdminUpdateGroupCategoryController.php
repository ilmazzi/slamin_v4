<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\UpdateGroupCategoryRequest;
use App\Http\Resources\Api\V1\Group\AdminGroupCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\AdminUpdateGroupCategoryAction;
use Domain\Group\Models\GroupCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdateGroupCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdateGroupCategoryAction $action,
    ) {
    }

    public function __invoke(UpdateGroupCategoryRequest $request, GroupCategory $groupCategory): JsonResponse
    {
        $category = $this->action->execute($groupCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminGroupCategoryResource($category),
        ]);
    }
}
