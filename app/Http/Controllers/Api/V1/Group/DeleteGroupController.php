<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\DeleteGroupAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class DeleteGroupController extends Controller
{
    public function __construct(
        private readonly DeleteGroupAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $this->action->execute($group, (int) request()->user()->id);

        return ApiResponse::success();
    }
}
