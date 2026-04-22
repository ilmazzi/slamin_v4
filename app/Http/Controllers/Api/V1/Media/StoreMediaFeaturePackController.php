<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\StoreMediaFeaturePackRequest;
use App\Http\Resources\Api\V1\Media\MediaFeaturePackResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\StoreMediaFeaturePackAction;
use Domain\Media\DTOs\StoreMediaFeaturePackData;
use Illuminate\Http\JsonResponse;

class StoreMediaFeaturePackController extends Controller
{
    public function __construct(
        private readonly StoreMediaFeaturePackAction $action,
    ) {
    }

    public function __invoke(StoreMediaFeaturePackRequest $request): JsonResponse
    {
        $featurePack = $this->action->execute(StoreMediaFeaturePackData::fromRequest($request));

        return ApiResponse::success([
            'feature_pack' => new MediaFeaturePackResource($featurePack),
        ], status: 201);
    }
}
