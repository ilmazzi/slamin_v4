<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupMemberResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\FollowGroupAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class FollowGroupController extends Controller
{
    public function __construct(
        private readonly FollowGroupAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('follow', $group);

        $membership = $this->action->execute($group, (int) request()->user()->id);

        return ApiResponse::success([
            'membership' => new GroupMemberResource($membership),
        ]);
    }
}
