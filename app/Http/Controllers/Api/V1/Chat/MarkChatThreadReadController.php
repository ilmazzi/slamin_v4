<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\MarkChatThreadReadAction;
use Domain\Chat\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkChatThreadReadController extends Controller
{
    public function __construct(
        private readonly MarkChatThreadReadAction $action,
    ) {
    }

    public function __invoke(Request $request, ChatThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $this->action->execute($thread->load('latestMessage'), $request->user()->id);

        return ApiResponse::success([
            'read' => true,
        ]);
    }
}
