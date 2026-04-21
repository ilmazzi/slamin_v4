<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\GetModerationDashboardStatsAction;
use Illuminate\Http\JsonResponse;

class GetModerationDashboardStatsController extends Controller
{
    public function __construct(
        private readonly GetModerationDashboardStatsAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'stats' => $this->action->execute(),
        ]);
    }
}
