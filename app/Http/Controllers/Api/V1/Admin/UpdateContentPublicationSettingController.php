<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateContentPublicationSettingRequest;
use App\Http\Resources\Api\V1\Admin\ContentPublicationSettingResource;
use App\Support\Responses\ApiResponse;
use Domain\Admin\Actions\UpdateContentPublicationSettingAction;
use Domain\Admin\Models\ContentPublicationSetting;
use Illuminate\Http\JsonResponse;

class UpdateContentPublicationSettingController extends Controller
{
    public function __construct(
        private readonly UpdateContentPublicationSettingAction $action,
    ) {
    }

    public function __invoke(
        UpdateContentPublicationSettingRequest $request,
        ContentPublicationSetting $contentPublicationSetting
    ): JsonResponse {
        $setting = $this->action->execute(
            $contentPublicationSetting,
            $request->validated(),
            $request->user()->id
        );

        return ApiResponse::success([
            'setting' => new ContentPublicationSettingResource($setting),
        ]);
    }
}
