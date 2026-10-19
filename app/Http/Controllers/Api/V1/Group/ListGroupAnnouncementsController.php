<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Group\GroupAnnouncementResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\ListGroupAnnouncementsAction;
use Domain\Group\Models\Group;
use Illuminate\Http\JsonResponse;

class ListGroupAnnouncementsController extends Controller
{
    public function __construct(
        private readonly ListGroupAnnouncementsAction $action,
    ) {
    }

    public function __invoke(Group $group): JsonResponse
    {
        $this->authorize('viewFeed', $group);

        $announcements = $this->action->execute($group);

        return ApiResponse::success([
            'announcements' => GroupAnnouncementResource::collection($announcements),
        ]);
    }
}
