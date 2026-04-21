<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Report\UpdateReportRequest;
use App\Http\Resources\Api\V1\Report\ReportResource;
use App\Support\Responses\ApiResponse;
use Domain\Report\Actions\UpdateReportAction;
use Domain\Report\Models\Report;
use Illuminate\Http\JsonResponse;

class UpdateReportController extends Controller
{
    public function __construct(
        private readonly UpdateReportAction $action,
    ) {
    }

    public function __invoke(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $report = $this->action->execute(
            report: $report,
            moderatorUserId: $request->user()->id,
            status: $request->validated('status'),
            resolutionNote: $request->validated('resolution_note'),
        );

        return ApiResponse::success([
            'report' => new ReportResource($report),
        ]);
    }
}
