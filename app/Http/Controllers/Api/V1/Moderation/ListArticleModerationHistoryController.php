<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Moderation\Actions\ListArticleModerationHistoryAction;
use Illuminate\Http\JsonResponse;

class ListArticleModerationHistoryController extends Controller
{
    public function __construct(
        private readonly ListArticleModerationHistoryAction $action,
    ) {
    }

    public function __invoke(Article $article): JsonResponse
    {
        return ApiResponse::success([
            'history' => ModerationDecisionResource::collection($this->action->execute($article)),
        ]);
    }
}
