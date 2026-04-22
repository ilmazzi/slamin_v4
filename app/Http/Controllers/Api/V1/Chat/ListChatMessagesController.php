<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\ListChatMessagesAction;
use Domain\Chat\Models\ChatThread;
use Illuminate\Http\JsonResponse;

class ListChatMessagesController extends Controller
{
    public function __construct(
        private readonly ListChatMessagesAction $action,
    ) {
    }

    public function __invoke(ChatThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $messages = $this->action->execute($thread);

        return ApiResponse::success([
            'messages' => ChatMessageResource::collection($messages),
        ]);
    }
}
