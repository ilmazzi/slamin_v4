<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Group\UpdateGroupAnnouncementRequest;
use App\Http\Resources\Api\V1\Group\GroupAnnouncementResource;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\UpdateGroupAnnouncementAction;
use Domain\Group\DTOs\UpdateGroupAnnouncementData;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Illuminate\Http\JsonResponse;

class UpdateGroupAnnouncementController extends Controller
{
    public function __construct(
        private readonly UpdateGroupAnnouncementAction $action,
    ) {
    }

    public function __invoke(UpdateGroupAnnouncementRequest $request, Group $group, GroupAnnouncement $announcement): JsonResponse
    {
        $announcement = $this->resolveAnnouncement($group, $announcement);
        $this->authorize('update', $announcement);

        $announcement = $this->action->execute($announcement, UpdateGroupAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => new GroupAnnouncementResource($announcement),
        ]);
    }

    private function resolveAnnouncement(Group $group, GroupAnnouncement $announcement): GroupAnnouncement
    {
        abort_unless($announcement->group_id === $group->id, 404);

        return $announcement;
    }
}
