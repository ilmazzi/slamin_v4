<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\ListArticleRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ListArticleAction;
use Domain\Article\DTOs\ListArticleData;
use Illuminate\Http\JsonResponse;

class ListArticleController extends Controller
{
    public function __construct(
        private readonly ListArticleAction $action,
    ) {
    }

    public function __invoke(ListArticleRequest $request): JsonResponse
    {
        $articles = $this->action->execute(
            ListArticleData::fromRequest($request),
            $request->user()?->id,
        );

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
