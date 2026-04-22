<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupMembersAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListGroupMembersController extends Controller
{
    public function __construct(
        private readonly ListGroupMembersAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('viewMembers', $group);

        $members = $this->action->execute($group);

        return ApiResponse::success([
            'members' => GroupMemberResource::collection($members),
        ]);
    }
}
