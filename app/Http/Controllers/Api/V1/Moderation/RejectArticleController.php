<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\RejectPoemRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Moderation\Actions\RejectArticleAction;
use Illuminate\Http\JsonResponse;

class RejectArticleController extends Controller
{
    public function __construct(
        private readonly RejectArticleAction $action,
    ) {
    }

    public function __invoke(RejectPoemRequest $request, Article $article): JsonResponse
    {
        $article = $this->action->execute($article, $request->user()->id, $request->validated('note'));

        return ApiResponse::success([
            'article' => new ArticleResource($article),
        ]);
    }
}
