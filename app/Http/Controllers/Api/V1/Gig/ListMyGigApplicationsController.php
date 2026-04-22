<?php

namespace App\Http\Controllers\Api\V1\Gig;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Gig\Actions\ListMyGigApplicationsAction;
use Domain\Gig\Models\GigApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyGigApplicationsController extends Controller
{
    public function __construct(
        private readonly ListMyGigApplicationsAction $action,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $applications = $this->action->execute($request->user()->id);

        return ApiResponse::success([
            'applications' => $applications->map(fn (GigApplication $application): array => [
                'id' => $application->id,
                'gig_id' => $application->gig_id,
                'status' => $application->status?->value,
                'message' => $application->message,
                'created_at' => $application->created_at?->toISOString(),
                'gig' => [
                    'id' => $application->gig?->id,
                    'type' => $application->gig?->type?->value,
                    'status' => $application->gig?->status?->value,
                ],
            ])->values()->all(),
        ]);
    }
}
