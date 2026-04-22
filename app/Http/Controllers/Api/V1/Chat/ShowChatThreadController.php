<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowChatThreadController extends Controller
{
    public function __invoke(Request $request, ChatThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $thread->load(['group', 'members.profile', 'memberRecords', 'latestMessage.user.profile']);

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ]);
    }
}
