<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\StoreGroupRequest;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\StoreGroupAction;
use Domain\Group\DTOs\StoreGroupData;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class StoreGroupController extends Controller
{
    public function __construct(
        private readonly StoreGroupAction $action,
    ) {
    }

    public function __invoke(StoreGroupRequest $request): JsonResponse
    {
        $this->authorize('create', Group::class);

        $group = $this->action->execute(StoreGroupData::fromRequest($request));

        return ApiResponse::success([
            'group' => new GroupResource($group),
        ]);
    }
}
