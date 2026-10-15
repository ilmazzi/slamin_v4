<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Gig\GigAnnouncementResource;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListGigAnnouncementsAction;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListGigAnnouncementsController extends Controller
{
    public function __construct(
        private readonly ListGigAnnouncementsAction $action,
    ) {}

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        $viewerId = (int) $request->user()->id;

        if (! $gig->isVisibleTo($viewerId)) {
            return ApiResponse::error(__('gig.not_available'), status: 404);
        }

        $announcements = $this->action->execute($gig, includeUnpublished: $gig->isManagedBy($viewerId));

        return ApiResponse::success([
            'announcements' => GigAnnouncementResource::collection($announcements),
        ]);
    }
}
