<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\AdminArticleCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;

class AdminShowArticleCategoryController extends Controller
{
    public function __invoke(ArticleCategory $articleCategory): JsonResponse
    {
        return ApiResponse::success([
            'category' => new AdminArticleCategoryResource($articleCategory->load('translations')),
        ]);
    }
}
