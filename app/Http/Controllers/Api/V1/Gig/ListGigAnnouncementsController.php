<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListGigAnnouncementsAction;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Http\JsonResponse;

class ListGigAnnouncementsController extends Controller
{
    public function __construct(
        private readonly ListGigAnnouncementsAction $action,
    ) {
    }

    public function __invoke(Gig $gig): JsonResponse
    {
        $announcements = $this->action->execute($gig);

        return ApiResponse::success([
            'announcements' => $announcements->map(fn (GigAnnouncement $announcement): array => [
                'id' => $announcement->id,
                'gig_id' => $announcement->gig_id,
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
                'published_at' => $announcement->published_at?->toISOString(),
                'author' => [
                    'id' => $announcement->author?->id,
                    'display_name' => $announcement->author?->profile?->display_name ?? $announcement->author?->profile?->nickname,
                ],
                'created_at' => $announcement->created_at?->toISOString(),
                'updated_at' => $announcement->updated_at?->toISOString(),
            ])->values()->all(),
        ]);
    }
}
