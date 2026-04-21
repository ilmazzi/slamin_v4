<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Moderation\Actions\ShowModerationArticleAction;
use Illuminate\Http\JsonResponse;

class ShowModerationArticleController extends Controller
{
    public function __construct(
        private readonly ShowModerationArticleAction $action,
    ) {
    }

    public function __invoke(Article $article): JsonResponse
    {
        $article = $this->action->execute($article);

        return ApiResponse::success([
            'article' => new ArticleResource($article),
            'moderation_history' => ModerationDecisionResource::collection($article->moderationDecisions),
        ]);
    }
}
