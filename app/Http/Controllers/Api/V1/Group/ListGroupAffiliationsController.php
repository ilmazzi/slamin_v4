<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupAffiliationResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupAffiliationsAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListGroupAffiliationsController extends Controller
{
    public function __construct(
        private readonly ListGroupAffiliationsAction $action,
    ) {}

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('viewAffiliations', $group);

        $lists = $this->action->execute($group);

        return ApiResponse::success([
            'parents' => GroupAffiliationResource::collection($lists['parents']),
            'children' => GroupAffiliationResource::collection($lists['children']),
            'incoming' => GroupAffiliationResource::collection($lists['incoming']),
            'outgoing' => GroupAffiliationResource::collection($lists['outgoing']),
        ]);
    }
}
