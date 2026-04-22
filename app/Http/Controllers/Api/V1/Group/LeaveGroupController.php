<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\LeaveGroupAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class LeaveGroupController extends Controller
{
    public function __construct(
        private readonly LeaveGroupAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('leave', $group);

        $this->action->execute($group, (int) request()->user()->id);

        return ApiResponse::success();
    }
}
