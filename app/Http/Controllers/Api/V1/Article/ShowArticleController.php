<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ShowArticleAction;
use Domain\Article\Enums\ArticleStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ShowArticleAction $action,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $article = $this->action->execute($slug, $request->user()?->id);

        if ($article->status === ArticleStatus::Published && $article->is_public && $article->user_id !== $request->user()?->id) {
            $article->increment('views_count');
        }

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
