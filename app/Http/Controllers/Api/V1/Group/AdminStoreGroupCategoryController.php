<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\StoreGroupCategoryRequest;
use App\Http\Resources\Api\V1\Group\AdminGroupCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\AdminStoreGroupCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStoreGroupCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStoreGroupCategoryAction $action,
    ) {
    }

    public function __invoke(StoreGroupCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminGroupCategoryResource($category),
        ], status: 201);
    }
}
