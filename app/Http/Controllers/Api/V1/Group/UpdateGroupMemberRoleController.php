<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\UpdateGroupMemberRoleRequest;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\UpdateGroupMemberRoleAction;
use Domain\Group\Enums\GroupMemberRole;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Illuminate\Http\JsonResponse;

class UpdateGroupMemberRoleController extends Controller
{
    public function __construct(
        private readonly UpdateGroupMemberRoleAction $action,
    ) {
    }

    public function __invoke(UpdateGroupMemberRoleRequest $request, Group $group, GroupMember $member): JsonResponse
    {
        $member = $this->resolveMember($group, $member);
        $this->authorize('manageRoles', [$group, $member]);

        $membership = $this->action->execute(
            $group,
            $member,
            GroupMemberRole::from($request->validated('role')),
            (int) $request->user()->id,
        );

        return ApiResponse::success([
            'membership' => new GroupMemberResource($membership),
        ]);
    }

    private function resolveMember(Group $group, GroupMember $member): GroupMember
    {
        abort_unless($member->group_id === $group->id, 404);

        return $member;
    }
}
