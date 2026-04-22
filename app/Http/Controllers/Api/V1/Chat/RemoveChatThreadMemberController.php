<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\RemoveChatThreadMemberAction;
use Domain\Chat\Models\ChatThread;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveChatThreadMemberController extends Controller
{
    public function __construct(
        private readonly RemoveChatThreadMemberAction $action,
    ) {
    }

    public function __invoke(Request $request, ChatThread $thread, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $thread);

        $thread = $this->action->execute(
            $thread,
            $user->id,
            $request->user()->id,
        );

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ]);
    }
}
