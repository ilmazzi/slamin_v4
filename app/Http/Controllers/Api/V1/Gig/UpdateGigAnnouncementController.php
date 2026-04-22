<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateGigAnnouncementRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateGigAnnouncementAction;
use Domain\Gig\DTOs\UpdateGigAnnouncementData;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Http\JsonResponse;

class UpdateGigAnnouncementController extends Controller
{
    public function __construct(
        private readonly UpdateGigAnnouncementAction $action,
    ) {
    }

    public function __invoke(UpdateGigAnnouncementRequest $request, Gig $gig, GigAnnouncement $announcement): JsonResponse
    {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        if ((int) $announcement->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.announcement_not_found'),
                status: 404,
            );
        }

        $announcement = $this->action->execute($announcement, UpdateGigAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => [
                'id' => $announcement->id,
                'gig_id' => $announcement->gig_id,
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
                'published_at' => $announcement->published_at?->toISOString(),
                'updated_at' => $announcement->updated_at?->toISOString(),
            ],
        ]);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
