<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ListFeaturedArticlesForAdminAction;
use Illuminate\Http\JsonResponse;

class ListAdminFeaturedArticlesController extends Controller
{
    public function __construct(
        private readonly ListFeaturedArticlesForAdminAction $action,
    ) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'articles' => ArticleResource::collection($this->action->execute()),
        ]);
    }
}
