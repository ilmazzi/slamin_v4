<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\StorePoemCategoryRequest;
use App\Http\Resources\Api\V1\Poetry\AdminPoemCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminStorePoemCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStorePoemCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStorePoemCategoryAction $action,
    ) {
    }

    public function __invoke(StorePoemCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminPoemCategoryResource($category),
        ], status: 201);
    }
}
