<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Queries\ListFeaturedArticlesQuery;
use Domain\Article\DTOs\ListFeaturedArticlesData;
use App\Http\Requests\Api\V1\Article\ListFeaturedArticlesRequest;

class ListFeaturedArticlesController extends Controller
{
    public function __construct(
        private readonly ListFeaturedArticlesQuery $listFeaturedArticlesQuery,
    ) {
    }

    public function __invoke(ListFeaturedArticlesRequest $request): JsonResponse
    {
        $articles = $this->listFeaturedArticlesQuery->execute(ListFeaturedArticlesData::fromRequest($request));

        return ApiResponse::success(
            data: ArticleResource::collection($articles->items()),
            meta: [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        );
    }
}
