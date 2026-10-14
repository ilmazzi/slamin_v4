<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ListMyArticlesAction;
use Illuminate\Http\JsonResponse;

class ListMyArticlesController extends Controller
{
    public function __construct(
        private readonly ListMyArticlesAction $action,
    ) {}

    public function __invoke(PaginationRequest $request): JsonResponse
    {
        $articles = $this->action->execute($request->user()->id, $request->perPage());

        return ApiResponse::success(
            data: ArticleResource::collection($articles->items()),
            meta: [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ]
        );
    }
}
