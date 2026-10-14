<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListPendingReviewArticlesAction;
use Illuminate\Http\JsonResponse;

class ListPendingReviewArticlesController extends Controller
{
    public function __construct(
        private readonly ListPendingReviewArticlesAction $action,
    ) {}

    public function __invoke(PaginationRequest $request): JsonResponse
    {
        $articles = $this->action->execute($request->perPage());

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
