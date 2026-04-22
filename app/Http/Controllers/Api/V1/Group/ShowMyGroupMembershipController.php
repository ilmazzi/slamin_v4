<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ShowMyGroupMembershipAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ShowMyGroupMembershipController extends Controller
{
    public function __construct(
        private readonly ShowMyGroupMembershipAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $membership = $this->action->execute($group, (int) request()->user()->id);

        return ApiResponse::success([
            'membership' => $membership ? new GroupMemberResource($membership) : null,
        ]);
    }
}
