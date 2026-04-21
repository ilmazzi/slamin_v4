<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ApprovePoemRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Moderation\Actions\ApproveArticleAction;
use Illuminate\Http\JsonResponse;

class ApproveArticleController extends Controller
{
    public function __construct(
        private readonly ApproveArticleAction $action,
    ) {
    }

    public function __invoke(ApprovePoemRequest $request, Article $article): JsonResponse
    {
        $article = $this->action->execute($article, $request->user()->id, $request->validated('note'));

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
