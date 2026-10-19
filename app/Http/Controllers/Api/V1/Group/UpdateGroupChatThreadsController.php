<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\UpdateGroupChatThreadsRequest;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\UpdateGroupChatThreadsAction;
use Domain\Group\DTOs\UpdateGroupChatThreadsData;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class UpdateGroupChatThreadsController extends Controller
{
    public function __construct(
        private readonly UpdateGroupChatThreadsAction $action,
    ) {}

    public function __invoke(UpdateGroupChatThreadsRequest $request, Group $group): JsonResponse
    {
        $this->authorize('manageChatThreads', $group);

        $threads = $this->action->execute(
            $group,
            UpdateGroupChatThreadsData::fromRequest($request)->audiences,
            (int) $request->user()->id,
        );

        return ApiResponse::success([
            'threads' => ChatThreadResource::collection($threads),
        ]);
    }
}
