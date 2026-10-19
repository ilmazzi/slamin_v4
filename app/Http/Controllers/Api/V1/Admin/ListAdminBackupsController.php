<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Backup\Actions\ListBackupsAction;
use Illuminate\Http\JsonResponse;

class ListAdminBackupsController extends Controller
{
    public function __invoke(ListBackupsAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute());
    }
}
