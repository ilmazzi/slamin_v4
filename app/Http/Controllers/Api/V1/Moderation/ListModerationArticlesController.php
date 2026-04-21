<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ListModerationArticlesRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListModerationArticlesAction;
use Domain\Moderation\DTOs\ListModerationArticlesData;
use Illuminate\Http\JsonResponse;

class ListModerationArticlesController extends Controller
{
    public function __construct(
        private readonly ListModerationArticlesAction $action,
    ) {
    }

    public function __invoke(ListModerationArticlesRequest $request): JsonResponse
    {
        $articles = $this->action->execute(ListModerationArticlesData::fromRequest($request));

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
