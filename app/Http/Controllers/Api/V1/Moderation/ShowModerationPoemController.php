<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Moderation\ModerationDecisionResource;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ShowModerationPoemAction;
use Domain\Poetry\Models\Poem;
use Illuminate\Http\JsonResponse;

class ShowModerationPoemController extends Controller
{
    public function __construct(
        private readonly ShowModerationPoemAction $action,
    ) {
    }

    public function __invoke(Poem $poem): JsonResponse
    {
        $poem = $this->action->execute($poem);

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
            'moderation_history' => ModerationDecisionResource::collection($poem->moderationDecisions),
        ]);
    }
}
