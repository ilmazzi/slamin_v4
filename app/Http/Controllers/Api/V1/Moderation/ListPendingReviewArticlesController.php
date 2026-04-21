<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListPendingReviewArticlesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListPendingReviewArticlesController extends Controller
{
    public function __construct(
        private readonly ListPendingReviewArticlesAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $articles = $this->action->execute((int) $request->query('per_page', 10));

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
