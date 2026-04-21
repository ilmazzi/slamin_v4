<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Report\ListReportsRequest;
use App\Http\Resources\Api\V1\Report\ReportResource;
use App\Support\Responses\ApiResponse;
use Domain\Report\Actions\ListReportsAction;
use Domain\Report\DTOs\ListReportsData;
use Illuminate\Http\JsonResponse;

class ListReportsController extends Controller
{
    public function __construct(
        private readonly ListReportsAction $action,
    ) {
    }

    public function __invoke(ListReportsRequest $request): JsonResponse
    {
        $reports = $this->action->execute(ListReportsData::fromRequest($request));

        return ApiResponse::success(
            data: ReportResource::collection($reports->items()),
            meta: [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ]
        );
    }
}
