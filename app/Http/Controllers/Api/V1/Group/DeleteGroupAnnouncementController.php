<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Group\Actions\DeleteGroupAnnouncementAction;
use Domain\Group\Models\Group;
use Domain\Group\Models\GroupAnnouncement;
use Illuminate\Http\JsonResponse;

class DeleteGroupAnnouncementController extends Controller
{
    public function __construct(
        private readonly DeleteGroupAnnouncementAction $action,
    ) {
    }

    public function __invoke(Group $group, GroupAnnouncement $announcement): JsonResponse
    {
        $announcement = $this->resolveAnnouncement($group, $announcement);
        $this->authorize('delete', $announcement);

        $this->action->execute($announcement, (int) request()->user()->id);

        return ApiResponse::success();
    }

    private function resolveAnnouncement(Group $group, GroupAnnouncement $announcement): GroupAnnouncement
    {
        abort_unless($announcement->group_id === $group->id, 404);

        return $announcement;
    }
}
