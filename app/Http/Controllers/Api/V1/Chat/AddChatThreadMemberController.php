<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\ManageChatThreadMemberRequest;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\AddChatThreadMemberAction;
use Domain\Chat\DTOs\ManageChatThreadMemberData;
use Domain\Chat\Models\ChatThread;
use Illuminate\Http\JsonResponse;

class AddChatThreadMemberController extends Controller
{
    public function __construct(
        private readonly AddChatThreadMemberAction $action,
    ) {
    }

    public function __invoke(ManageChatThreadMemberRequest $request, ChatThread $thread): JsonResponse
    {
        $this->authorize('manageMembers', $thread);

        $thread = $this->action->execute(
            $thread,
            ManageChatThreadMemberData::fromRequest($request)->userId,
            $request->user()->id,
        );

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ]);
    }
}
