<?php

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\DeleteArticleAction;
use Domain\Article\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteArticleController extends Controller
{
    public function __construct(
        private readonly DeleteArticleAction $action,
    ) {
    }

    public function __invoke(Request $request, Article $article): JsonResponse
    {
        $this->authorize('delete', $article);

        $this->action->execute($article, $request->user()->id);

        return ApiResponse::success();
    }
}
