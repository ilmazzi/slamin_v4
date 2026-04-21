<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Report\StoreReportRequest;
use App\Http\Resources\Api\V1\Report\ReportResource;
use App\Support\Responses\ApiResponse;
use Domain\Article\Models\Article;
use Domain\Report\Actions\StoreReportAction;
use Illuminate\Http\JsonResponse;

class StoreArticleReportController extends Controller
{
    public function __construct(
        private readonly StoreReportAction $action,
    ) {
    }

    public function __invoke(StoreReportRequest $request, Article $article): JsonResponse
    {
        $report = $this->action->execute(
            reportable: $article,
            reporterUserId: $request->user()->id,
            reason: $request->validated('reason'),
            details: $request->validated('details'),
        );

        return ApiResponse::success([
            'report' => new ReportResource($report),
        ]);
    }
}
