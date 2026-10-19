<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\StoreGroupRoleRequest;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\RequestGroupRoleAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class RequestGroupRoleController extends Controller
{
    public function __construct(
        private readonly RequestGroupRoleAction $action,
    ) {
    }

    public function __invoke(StoreGroupRoleRequest $request, Group $group): JsonResponse
    {
        $this->authorize('requestRole', $group);

        $membership = $this->action->execute(
            $group,
            (int) $request->user()->id,
            GroupMemberRole::from($request->validated('requested_role')),
        );

        return ApiResponse::success([
            'membership' => new GroupMemberResource($membership),
        ]);
    }
}
