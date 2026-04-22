<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListPendingGroupRequestsAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListPendingGroupRequestsController extends Controller
{
    public function __construct(
        private readonly ListPendingGroupRequestsAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('viewRequests', $group);

        $requests = $this->action->execute($group);

        return ApiResponse::success([
            'requests' => GroupMemberResource::collection($requests),
        ]);
    }
}
