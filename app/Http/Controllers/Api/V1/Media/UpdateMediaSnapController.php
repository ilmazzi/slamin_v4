<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\UpdateMediaSnapRequest;
use App\Http\Resources\Api\V1\Media\MediaSnapResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\UpdateMediaSnapAction;
use Domain\Media\DTOs\UpdateMediaSnapData;
use Domain\Media\Models\MediaItem;
use Domain\Media\Models\MediaSnap;
use Illuminate\Http\JsonResponse;

class UpdateMediaSnapController extends Controller
{
    public function __construct(
        private readonly UpdateMediaSnapAction $action,
    ) {
    }

    public function __invoke(UpdateMediaSnapRequest $request, MediaItem $media, MediaSnap $snap): JsonResponse
    {
        abort_unless($snap->media_item_id === $media->id, 404);
        $this->authorize('update', $snap);

        $snap = $this->action->execute($snap, UpdateMediaSnapData::fromRequest($request));

        return ApiResponse::success([
            'snap' => new MediaSnapResource($snap),
        ]);
    }
}
