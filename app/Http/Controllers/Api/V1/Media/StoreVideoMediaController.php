<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\StoreVideoMediaRequest;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\StoreVideoMediaAction;
use Domain\Media\DTOs\StoreVideoMediaData;
use Illuminate\Http\JsonResponse;

class StoreVideoMediaController extends Controller
{
    public function __construct(
        private readonly StoreVideoMediaAction $action,
    ) {
    }

    public function __invoke(StoreVideoMediaRequest $request): JsonResponse
    {
        $media = $this->action->execute(StoreVideoMediaData::fromRequest($request));

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ], status: 201);
    }
}
