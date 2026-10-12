<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\StoreHelpArticleRequest;
use App\Http\Resources\Api\V1\Help\AdminHelpArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminStoreHelpArticleAction;
use Illuminate\Http\JsonResponse;

class AdminStoreHelpArticleController extends Controller
{
    public function __construct(
        private readonly AdminStoreHelpArticleAction $action,
    ) {}

    public function __invoke(StoreHelpArticleRequest $request): JsonResponse
    {
        $article = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'article' => new AdminHelpArticleResource($article),
        ], status: 201);
    }
}
