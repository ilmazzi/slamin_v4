<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\StoreDirectChatThreadRequest;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\EnsureDirectChatThreadAction;
use Domain\Chat\DTOs\StoreDirectChatThreadData;
use Illuminate\Http\JsonResponse;

class StoreDirectChatThreadController extends Controller
{
    public function __construct(
        private readonly EnsureDirectChatThreadAction $action,
    ) {
    }

    public function __invoke(StoreDirectChatThreadRequest $request): JsonResponse
    {
        $thread = $this->action->execute(
            $request->user()->id,
            StoreDirectChatThreadData::fromRequest($request)->targetUserId,
        );

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ], status: 201);
    }
}
