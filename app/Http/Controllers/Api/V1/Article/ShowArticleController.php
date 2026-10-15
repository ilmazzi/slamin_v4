<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\ShowArticleAction;
use Domain\Article\Enums\ArticleStatus;
use Domain\ContentTranslation\Actions\LoadContentTranslationsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ShowArticleAction $action,
        private readonly LoadContentTranslationsAction $loadTranslations,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $authUserId = auth('sanctum')->id();

        $article = $this->action->execute($slug, $authUserId);

        if ($article->status === ArticleStatus::Published && $article->is_public && $article->user_id !== $authUserId) {
            $article->increment('views_count');
        }

        $this->loadTranslations->execute($article, app()->getLocale());

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
