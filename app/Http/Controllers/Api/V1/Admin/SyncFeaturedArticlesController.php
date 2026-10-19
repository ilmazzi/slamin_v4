<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SyncFeaturedArticlesRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Actions\SyncFeaturedArticlesAction;
use Domain\Article\DTOs\SyncFeaturedArticlesData;
use Illuminate\Http\JsonResponse;

class SyncFeaturedArticlesController extends Controller
{
    public function __construct(
        private readonly SyncFeaturedArticlesAction $action,
    ) {}

    public function __invoke(SyncFeaturedArticlesRequest $request): JsonResponse
    {
        $articles = $this->action->execute(
            SyncFeaturedArticlesData::fromRequest($request),
            $request->user()->id,
        );

        return ApiResponse::success([
            'articles' => ArticleResource::collection($articles),
        ]);
    }
}
