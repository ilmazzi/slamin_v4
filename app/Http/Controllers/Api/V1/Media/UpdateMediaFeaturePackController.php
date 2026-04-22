<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\UpdateMediaFeaturePackRequest;
use App\Http\Resources\Api\V1\Media\MediaFeaturePackResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\UpdateMediaFeaturePackAction;
use Domain\Media\DTOs\UpdateMediaFeaturePackData;
use Domain\Media\Models\MediaFeaturePack;
use Illuminate\Http\JsonResponse;

class UpdateMediaFeaturePackController extends Controller
{
    public function __construct(
        private readonly UpdateMediaFeaturePackAction $action,
    ) {
    }

    public function __invoke(UpdateMediaFeaturePackRequest $request, MediaFeaturePack $featurePack): JsonResponse
    {
        $featurePack = $this->action->execute($featurePack, UpdateMediaFeaturePackData::fromRequest($request));

        return ApiResponse::success([
            'feature_pack' => new MediaFeaturePackResource($featurePack),
        ]);
    }
}
