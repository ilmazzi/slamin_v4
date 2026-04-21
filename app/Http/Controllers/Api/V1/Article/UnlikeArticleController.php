<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\UnlikeArticleAction;
use Domain\Article\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnlikeArticleController extends Controller
{
    public function __construct(
        private readonly UnlikeArticleAction $action,
    ) {
    }

    public function __invoke(Request $request, Article $article): JsonResponse
    {
        $this->authorize('like', $article);
        $this->action->execute($article, $request->user()->id);

        return ApiResponse::success([
            'article' => new ArticleResource($article->fresh(['user.profile', 'category.translations'])),
        ]);
    }
}
