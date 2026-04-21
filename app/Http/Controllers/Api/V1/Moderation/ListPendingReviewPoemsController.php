<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ListModerationPoemsRequest;
use Domain\Moderation\Actions\ListModerationPoemsAction;
use Domain\Moderation\DTOs\ListModerationPoemsData;
use Illuminate\Http\JsonResponse;

class ListPendingReviewPoemsController extends Controller
{
    public function __construct(
        private readonly ListModerationPoemsAction $action,
    ) {
    }

    public function __invoke(ListModerationPoemsRequest $request): JsonResponse
    {
        $request->merge(['status' => 'pending_review']);

        return app(ListModerationPoemsController::class)($request);
    }
}
