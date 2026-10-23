<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forum\StoreForumCategoryRequest;
use App\Http\Resources\Api\V1\Forum\AdminForumCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Forum\Actions\AdminStoreForumCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStoreForumCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStoreForumCategoryAction $action,
    ) {}

    public function __invoke(StoreForumCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminForumCategoryResource($category->loadCount('posts')),
        ], status: 201);
    }
}
