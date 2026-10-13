<?php

namespace App\Http\Controllers\Api\V1\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Help\UpdateHelpArticleRequest;
use App\Http\Resources\Api\V1\Help\AdminHelpArticleResource;
use App\Support\Responses\ApiResponse;
use Domain\Help\Actions\AdminUpdateHelpArticleAction;
use Domain\Help\Models\HelpArticle;
use Illuminate\Http\JsonResponse;

class AdminUpdateHelpArticleController extends Controller
{
    public function __construct(
        private readonly AdminUpdateHelpArticleAction $action,
    ) {}

    public function __invoke(UpdateHelpArticleRequest $request, HelpArticle $helpArticle): JsonResponse
    {
        $article = $this->action->execute($helpArticle, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'article' => new AdminHelpArticleResource($article),
        ]);
    }
}
