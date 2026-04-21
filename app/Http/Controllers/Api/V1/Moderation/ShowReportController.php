<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Report\ReportResource;
use App\Support\Responses\ApiResponse;
use Domain\Report\Actions\ShowReportAction;
use Domain\Report\Models\Report;
use Illuminate\Http\JsonResponse;

class ShowReportController extends Controller
{
    public function __construct(
        private readonly ShowReportAction $action,
    ) {
    }

    public function __invoke(Report $report): JsonResponse
    {
        return ApiResponse::success([
            'report' => new ReportResource($this->action->execute($report)),
        ]);
    }
}
