<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\StoreHelpCategoryRequest;
use App\Http\Resources\Api\V1\Help\AdminHelpCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminStoreHelpCategoryAction;
use Illuminate\Http\JsonResponse;

class AdminStoreHelpCategoryController extends Controller
{
    public function __construct(
        private readonly AdminStoreHelpCategoryAction $action,
    ) {}

    public function __invoke(StoreHelpCategoryRequest $request): JsonResponse
    {
        $category = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminHelpCategoryResource($category),
        ], status: 201);
    }
}
