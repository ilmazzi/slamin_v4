<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListPoemModerationHistoryAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class ListPoemModerationHistoryController extends Controller
{
    public function __construct(
        private readonly ListPoemModerationHistoryAction $action,
    ) {
    }

    public function __invoke(Poem $poem): JsonResponse
    {
        return ApiResponse::success([
            'history' => ModerationDecisionResource::collection($this->action->execute($poem)),
        ]);
    }
}
