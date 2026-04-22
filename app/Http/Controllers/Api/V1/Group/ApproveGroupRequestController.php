<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ApproveGroupRequestAction;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupMember;
use Illuminate\Http\JsonResponse;

class ApproveGroupRequestController extends Controller
{
    public function __construct(
        private readonly ApproveGroupRequestAction $action,
    ) {
    }

    public function __invoke(Group $group, GroupMember $member): JsonResponse
    {
        $this->authorize('approveRequests', $group);

        $membership = $this->action->execute($group, $this->resolveMember($group, $member), (int) request()->user()->id);

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
