<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Dashboard\Actions\GetPersonalStatisticsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetPersonalStatisticsController extends Controller
{
    public function __invoke(
        Request $request,
        GetPersonalStatisticsAction $getPersonalStatistics,
    ): JsonResponse {
        return ApiResponse::success(
            $getPersonalStatistics->execute($request->user()),
        );
    }
}
