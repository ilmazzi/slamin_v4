<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\ListGroupsRequest;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListMyGroupsAction;
use Illuminate\Http\JsonResponse;

class ListMyGroupsController extends Controller
{
    public function __construct(
        private readonly ListMyGroupsAction $action,
    ) {
    }

    public function __invoke(ListGroupsRequest $request): JsonResponse
    {
        $groups = $this->action->execute(
            userId: (int) $request->user()->id,
            perPage: (int) $request->validated('per_page', 10),
        );

        return ApiResponse::success(
            data: [
                'groups' => GroupResource::collection($groups->items()),
            ],
            meta: [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        );
    }
}
