<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ListArticleCategoriesAction;
use Illuminate\Http\JsonResponse;

class ListArticleCategoriesController extends Controller
{
    public function __construct(
        private readonly ListArticleCategoriesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => ArticleCategoryResource::collection($this->action->execute()),
        ]);
    }
}
