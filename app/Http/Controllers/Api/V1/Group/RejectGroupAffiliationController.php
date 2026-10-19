<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupAffiliationResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\RejectGroupAffiliationAction;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAffiliation;
use Illuminate\Http\JsonResponse;

class RejectGroupAffiliationController extends Controller
{
    public function __construct(
        private readonly RejectGroupAffiliationAction $action,
    ) {}

    public function __invoke(Group $group, GroupAffiliation $affiliation): JsonResponse
    {
        abort_unless($affiliation->involves($group), 404);
        $this->authorize('manageAffiliations', $group);

        $affiliation = $this->action->execute($group, $affiliation, (int) request()->user()->id);

        return ApiResponse::success([
            'affiliation' => new GroupAffiliationResource($affiliation),
        ]);
    }
}
