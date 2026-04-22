<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListGigApplicationsAction;
use Domain\Gig\Models\Gig;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListGigApplicationsController extends Controller
{
    public function __construct(
        private readonly ListGigApplicationsAction $action,
    ) {
    }

    public function __invoke(Request $request, Gig $gig): JsonResponse
    {
        abort_unless($this->canManage($request->user()->id, $gig), 403);

        $applications = $this->action->execute($gig);

        return ApiResponse::success([
            'applications' => $applications->map(fn (GigApplication $application): array => [
                'id' => $application->id,
                'gig_id' => $application->gig_id,
                'user_id' => $application->user_id,
                'message' => $application->message,
                'status' => $application->status?->value,
                'reviewed_at' => $application->reviewed_at?->toISOString(),
                'confirmed_at' => $application->confirmed_at?->toISOString(),
                'withdrawn_at' => $application->withdrawn_at?->toISOString(),
                'user' => [
                    'id' => $application->user?->id,
                    'display_name' => $application->user?->profile?->display_name ?? $application->user?->profile?->nickname,
                    'email' => $application->user?->email,
                ],
            ])->values()->all(),
        ]);
    }

    private function canManage(int $userId, Gig $gig): bool
    {
        $parent = $gig->giggable;

        return $parent !== null && (int) ($parent->user_id ?? 0) === $userId;
    }
}
