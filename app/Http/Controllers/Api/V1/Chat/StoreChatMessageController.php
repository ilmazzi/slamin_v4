<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\StoreChatMessageRequest;
use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\StoreChatMessageAction;
use Domain\Chat\DTOs\StoreChatMessageData;
use Domain\Chat\Models\ChatThread;
use Illuminate\Http\JsonResponse;

class StoreChatMessageController extends Controller
{
    public function __construct(
        private readonly StoreChatMessageAction $action,
    ) {
    }

    public function __invoke(StoreChatMessageRequest $request, ChatThread $thread): JsonResponse
    {
        $this->authorize('send', $thread);

        $message = $this->action->execute(
            $thread,
            $request->user()->id,
            StoreChatMessageData::fromRequest($request),
        );

        return ApiResponse::success([
            'message' => new ChatMessageResource($message),
        ], status: 201);
    }
}
