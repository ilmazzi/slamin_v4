<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\StoreArticleRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\StoreArticleAction;
use Domain\Article\DTOs\StoreArticleData;
use Illuminate\Http\JsonResponse;

class StoreArticleController extends Controller
{
    public function __construct(
        private readonly StoreArticleAction $action,
    ) {
    }

    public function __invoke(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->action->execute(StoreArticleData::fromRequest($request));

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
