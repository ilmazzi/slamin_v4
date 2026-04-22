<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\StoreMediaSnapRequest;
use App\Http\Resources\Api\V1\Media\MediaSnapResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\StoreMediaSnapAction;
use Domain\Media\DTOs\StoreMediaSnapData;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Illuminate\Http\JsonResponse;

class StoreMediaSnapController extends Controller
{
    public function __construct(
        private readonly StoreMediaSnapAction $action,
    ) {
    }

    public function __invoke(StoreMediaSnapRequest $request, MediaItem $media): JsonResponse
    {
        $this->authorize('create', [MediaSnap::class, $media]);

        $snap = $this->action->execute(
            $media,
            $request->user()->id,
            StoreMediaSnapData::fromRequest($request),
        );

        return ApiResponse::success([
            'snap' => new MediaSnapResource($snap),
        ], status: 201);
    }
}
