<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\StoreGroupAnnouncementRequest;
use App\Http\Resources\Api\V1\Group\GroupAnnouncementResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\StoreGroupAnnouncementAction;
use Domain\Group\DTOs\StoreGroupAnnouncementData;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class StoreGroupAnnouncementController extends Controller
{
    public function __construct(
        private readonly StoreGroupAnnouncementAction $action,
    ) {
    }

    public function __invoke(StoreGroupAnnouncementRequest $request, Group $group): JsonResponse
    {
        $this->authorize('publishAnnouncements', $group);

        $announcement = $this->action->execute($group, StoreGroupAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => new GroupAnnouncementResource($announcement),
        ]);
    }
}
