<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\ManageChatMessageReactionRequest;
use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\DeleteChatMessageReactionAction;
use Domain\Chat\DTOs\ManageChatMessageReactionData;
use Domain\Chat\Models\ChatMessage;
use Illuminate\Http\JsonResponse;

class DeleteChatMessageReactionController extends Controller
{
    public function __construct(
        private readonly DeleteChatMessageReactionAction $action,
    ) {
    }

    public function __invoke(ManageChatMessageReactionRequest $request, ChatMessage $message): JsonResponse
    {
        $this->authorize('view', $message->thread);

        $this->action->execute(
            $message,
            $request->user()->id,
            ManageChatMessageReactionData::fromRequest($request),
        );

        $message->load(['user.profile', 'replyTo.user.profile', 'reactions.user.profile']);

        return ApiResponse::success([
            'message' => new ChatMessageResource($message),
        ]);
    }
}
