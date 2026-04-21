<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\AdminDeleteArticleCategoryAction;
use Domain\Article\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;

class AdminDeleteArticleCategoryController extends Controller
{
    public function __construct(
        private readonly AdminDeleteArticleCategoryAction $action,
    ) {
    }

    public function __invoke(ArticleCategory $articleCategory): JsonResponse
    {
        if (! $this->action->execute($articleCategory, request()->user()->id)) {
            return ApiResponse::error(
                message: 'Cannot delete a category that is already used by one or more articles.',
                status: 422
            );
        }

        return ApiResponse::success();
    }
}
