<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\ContentPublicationSettingResource;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\ListContentPublicationSettingsAction;
use Illuminate\Http\JsonResponse;

class ListContentPublicationSettingsController extends Controller
{
    public function __construct(
        private readonly ListContentPublicationSettingsAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'settings' => ContentPublicationSettingResource::collection($this->action->execute()),
        ]);
    }
}
