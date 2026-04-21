<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\AdminArticleCategoryResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\AdminListArticleCategoriesAction;
use Illuminate\Http\JsonResponse;

class AdminListArticleCategoriesController extends Controller
{
    public function __construct(
        private readonly AdminListArticleCategoriesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'categories' => AdminArticleCategoryResource::collection($this->action->execute()),
        ]);
    }
}
