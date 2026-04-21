<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\StoreEventCategoryRequest;
use App\Http\Resources\Api\V1\Event\AdminEventCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Event\Actions\AdminStoreEventCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStoreEventCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStoreEventCategoryAction $action,
    ) {
    }

    public function __invoke(StoreEventCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminEventCategoryResource($category),
        ], status: 201);
    }
}
