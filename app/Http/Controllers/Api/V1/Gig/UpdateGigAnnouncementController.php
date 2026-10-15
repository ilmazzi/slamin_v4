<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateGigAnnouncementRequest;
use App\Http\Resources\Api\V1\Gig\GigAnnouncementResource;
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
    ) {}

    public function __invoke(UpdateGigAnnouncementRequest $request, Gig $gig, GigAnnouncement $announcement): JsonResponse
    {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        if ((int) $announcement->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.announcement_not_found'),
                status: 404,
            );
        }

        $announcement = $this->action->execute($announcement, UpdateGigAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => new GigAnnouncementResource($announcement),
        ]);
    }
}
