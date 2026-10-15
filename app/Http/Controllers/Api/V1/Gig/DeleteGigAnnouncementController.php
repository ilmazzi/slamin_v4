<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\DeleteGigAnnouncementAction;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigAnnouncement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteGigAnnouncementController extends Controller
{
    public function __construct(
        private readonly DeleteGigAnnouncementAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig, GigAnnouncement $announcement): JsonResponse
    {
        abort_unless($gig->isManagedBy((int) $request->user()->id), 403);

        if ((int) $announcement->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.announcement_not_found'),
                status: 404,
            );
        }

        $this->action->execute($announcement);

        return ApiResponse::success([
            'deleted' => true,
        ]);
    }
}
