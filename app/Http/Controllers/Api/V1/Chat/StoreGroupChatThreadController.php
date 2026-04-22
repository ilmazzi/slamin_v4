<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\StoreGroupChatThreadRequest;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\StoreGroupChatThreadAction;
use Domain\Chat\DTOs\StoreGroupChatThreadData;
use Illuminate\Http\JsonResponse;

class StoreGroupChatThreadController extends Controller
{
    public function __construct(
        private readonly StoreGroupChatThreadAction $action,
    ) {
    }

    public function __invoke(StoreGroupChatThreadRequest $request): JsonResponse
    {
        $thread = $this->action->execute(
            $request->user()->id,
            StoreGroupChatThreadData::fromRequest($request),
        );

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ], status: 201);
    }
}
