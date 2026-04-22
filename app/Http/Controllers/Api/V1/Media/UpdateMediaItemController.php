<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Media\UpdateMediaItemRequest;
use App\Http\Resources\Api\V1\Media\MediaItemResource;
use App\Support\Responses\ApiResponse;
use Domain\Media\Actions\UpdateMediaItemAction;
use Domain\Media\DTOs\UpdateMediaItemData;
use Domain\Media\Models\MediaItem;
use Illuminate\Http\JsonResponse;

class UpdateMediaItemController extends Controller
{
    public function __construct(
        private readonly UpdateMediaItemAction $action,
    ) {
    }

    public function __invoke(UpdateMediaItemRequest $request, MediaItem $media): JsonResponse
    {
        $this->authorize('update', $media);

        $media = $this->action->execute($media, UpdateMediaItemData::fromRequest($request), $request->user()->id);

        return ApiResponse::success([
            'media' => new MediaItemResource($media),
        ]);
    }
}
