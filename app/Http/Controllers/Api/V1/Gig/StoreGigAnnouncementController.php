<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\StoreGigAnnouncementRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\StoreGigAnnouncementAction;
use Domain\Gig\DTOs\StoreGigAnnouncementData;
use Domain\Gig\Models\Gig;
use Illuminate\Http\JsonResponse;

class StoreGigAnnouncementController extends Controller
{
    public function __construct(
        private readonly StoreGigAnnouncementAction $action,
    ) {
    }

    public function __invoke(StoreGigAnnouncementRequest $request, Gig $gig): JsonResponse
    {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        $announcement = $this->action->execute($gig, StoreGigAnnouncementData::fromRequest($request));

        return ApiResponse::success([
            'announcement' => [
                'id' => $announcement->id,
                'gig_id' => $announcement->gig_id,
                'message' => $announcement->message,
                'status' => $announcement->status?->value,
                'published_at' => $announcement->published_at?->toISOString(),
                'created_at' => $announcement->created_at?->toISOString(),
            ],
        ], status: 201);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
