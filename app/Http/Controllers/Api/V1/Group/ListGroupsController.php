<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\ListGroupsRequest;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupsAction;
use Domain\Group\DTOs\ListGroupsData;
use Illuminate\Http\JsonResponse;

class ListGroupsController extends Controller
{
    public function __construct(
        private readonly ListGroupsAction $action,
    ) {
    }

    public function __invoke(ListGroupsRequest $request): JsonResponse
    {
        $groups = $this->action->execute(
            ListGroupsData::fromRequest($request),
            $request->user()?->id,
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
