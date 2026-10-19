<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Backup\Actions\RunBackupAction;
use Illuminate\Http\JsonResponse;

class RunAdminBackupController extends Controller
{
    public function __invoke(RunBackupAction $action): JsonResponse
    {
        $action->execute();

        return ApiResponse::success([
            'queued' => true,
        ]);
    }
}
