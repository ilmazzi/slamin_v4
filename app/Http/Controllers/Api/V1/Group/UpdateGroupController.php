<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\UpdateGroupRequest;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\UpdateGroupAction;
use Domain\Group\DTOs\UpdateGroupData;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class UpdateGroupController extends Controller
{
    public function __construct(
        private readonly UpdateGroupAction $action,
    ) {
    }

    public function __invoke(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        $group = $this->action->execute($group, UpdateGroupData::fromRequest($request));

        return ApiResponse::success([
            'group' => new GroupResource($group),
        ]);
    }
}
