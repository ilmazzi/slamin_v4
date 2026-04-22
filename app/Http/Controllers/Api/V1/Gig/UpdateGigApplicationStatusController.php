<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Gig\UpdateGigApplicationStatusRequest;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\UpdateGigApplicationStatusAction;
use Domain\Gig\DTOs\UpdateGigApplicationStatusData;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateGigApplicationStatusController extends Controller
{
    public function __construct(
        private readonly UpdateGigApplicationStatusAction $action,
    ) {
    }

    public function __invoke(
        UpdateGigApplicationStatusRequest $request,
        Gig $gig,
        GigApplication $application,
    ): JsonResponse {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        if ((int) $application->gig_id !== (int) $gig->id) {
            return ApiResponse::error(
                message: __('gig.application_not_found'),
                status: 404,
            );
        }

        $application = $this->action->execute($gig, $application, UpdateGigApplicationStatusData::fromRequest($request));

        return ApiResponse::success([
            'application' => [
                'id' => $application->id,
                'gig_id' => $application->gig_id,
                'user_id' => $application->user_id,
                'status' => $application->status?->value,
                'reviewed_at' => $application->reviewed_at?->toISOString(),
                'confirmed_at' => $application->confirmed_at?->toISOString(),
            ],
            'gig' => [
                'id' => $gig->fresh()->id,
                'filled_quantity' => $gig->fresh()->filled_quantity,
                'status' => $gig->fresh()->status?->value,
            ],
        ]);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
