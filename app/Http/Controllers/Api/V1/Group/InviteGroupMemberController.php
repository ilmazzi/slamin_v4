<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\InviteGroupMemberRequest;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\InviteGroupMemberAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class InviteGroupMemberController extends Controller
{
    public function __construct(
        private readonly InviteGroupMemberAction $action,
    ) {
    }

    public function __invoke(InviteGroupMemberRequest $request, Group $group): JsonResponse
    {
        $this->authorize('invite', $group);

        $membership = $this->action->execute(
            $group,
            (int) $request->user()->id,
            (int) $request->validated('user_id'),
            GroupMemberRole::from($request->validated('role')),
        );

        return ApiResponse::success([
            'membership' => new GroupMemberResource($membership),
        ]);
    }
}
