<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\SearchProposeableGroupsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchProposeableGroupsController extends Controller
{
    public function __construct(
        private readonly SearchProposeableGroupsAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $groups = $this->action->execute((int) $request->user()->id, $validated['q']);

        return ApiResponse::success([
            'groups' => GroupResource::collection($groups),
        ]);
    }
}
