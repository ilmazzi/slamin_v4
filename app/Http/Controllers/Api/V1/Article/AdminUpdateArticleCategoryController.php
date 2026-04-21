<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\UpdateArticleCategoryRequest;
use App\Http\Resources\Api\V1\Article\AdminArticleCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\AdminUpdateArticleCategoryAction;
use Domain\Article\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;

class AdminUpdateArticleCategoryController extends Controller
{
    public function __construct(
        private readonly AdminUpdateArticleCategoryAction $action,
    ) {
    }

    public function __invoke(UpdateArticleCategoryRequest $request, ArticleCategory $articleCategory): JsonResponse
    {
        $category = $this->action->execute($articleCategory, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'category' => new AdminArticleCategoryResource($category),
        ]);
    }
}
