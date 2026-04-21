<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\UpdateArticleRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\UpdateArticleAction;
use Domain\Article\DTOs\UpdateArticleData;
use Domain\Article\Models\Article;
use Illuminate\Http\JsonResponse;

class UpdateArticleController extends Controller
{
    public function __construct(
        private readonly UpdateArticleAction $action,
    ) {
    }

    public function __invoke(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $this->authorize('update', $article);

        $article = $this->action->execute($article, UpdateArticleData::fromRequest($request));

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
