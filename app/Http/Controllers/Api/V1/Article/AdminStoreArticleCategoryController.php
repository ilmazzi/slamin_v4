<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\StoreArticleCategoryRequest;
use App\Http\Resources\Api\V1\Article\AdminArticleCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\AdminStoreArticleCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStoreArticleCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStoreArticleCategoryAction $action,
    ) {
    }

    public function __invoke(StoreArticleCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminArticleCategoryResource($category),
        ], status: 201);
    }
}
