<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\StorePhotoMediaRequest;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\StorePhotoMediaAction;
use Domain\Media\DTOs\StorePhotoMediaData;
use Illuminate\Http\JsonResponse;

class StorePhotoMediaController extends Controller
{
    public function __construct(
        private readonly StorePhotoMediaAction $action,
    ) {
    }

    public function __invoke(StorePhotoMediaRequest $request): JsonResponse
    {
        $media = $this->action->execute(StorePhotoMediaData::fromRequest($request));

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ], status: 201);
    }
}
