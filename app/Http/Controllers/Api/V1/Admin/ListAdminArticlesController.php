<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListAdminArticlesRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\DTOs\ListAdminArticlesData;
use Domain\Article\Queries\ListAdminArticlesQuery;
use Illuminate\Http\JsonResponse;

class ListAdminArticlesController extends Controller
{
    public function __construct(
        private readonly ListAdminArticlesQuery $query,
    ) {}

    public function __invoke(ListAdminArticlesRequest $request): JsonResponse
    {
        $articles = $this->query->execute(ListAdminArticlesData::fromRequest($request));

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
