<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\StoreGroupAffiliationRequest;
use App\Http\Resources\Api\V1\Group\GroupAffiliationResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\RequestGroupAffiliationAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class StoreGroupAffiliationController extends Controller
{
    public function __construct(
        private readonly RequestGroupAffiliationAction $action,
    ) {}

    public function __invoke(StoreGroupAffiliationRequest $request, Group $group): JsonResponse
    {
        $this->authorize('manageAffiliations', $group);

        $affiliation = $this->action->execute(
            $group,
            (int) $request->user()->id,
            $request->validated('parent_group_id') !== null ? (int) $request->validated('parent_group_id') : null,
            $request->validated('child_group_id') !== null ? (int) $request->validated('child_group_id') : null,
        );

        return ApiResponse::success([
            'affiliation' => new GroupAffiliationResource($affiliation),
        ]);
    }
}
