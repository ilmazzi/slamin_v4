<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\TestPeerTubeConnectionAction;
use Illuminate\Http\JsonResponse;

class TestPeerTubeConnectionController extends Controller
{
    public function __construct(
        private readonly TestPeerTubeConnectionAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'connected' => $this->action->execute(),
        ]);
    }
}
