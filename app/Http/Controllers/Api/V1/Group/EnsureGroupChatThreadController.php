<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Chat\Actions\EnsureGroupChatThreadAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureGroupChatThreadController extends Controller
{
    public function __construct(
        private readonly EnsureGroupChatThreadAction $action,
    ) {
    }

    public function __invoke(Request $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        $thread = $this->action->execute($group, $request->user()->id);

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
        ], status: 201);
    }
}
