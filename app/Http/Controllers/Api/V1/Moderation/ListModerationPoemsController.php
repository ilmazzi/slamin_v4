<?php

namespace App\Http\Controllers\Api\V1\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Moderation\ListModerationPoemsRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Moderation\Actions\ListModerationPoemsAction;
use Domain\Moderation\DTOs\ListModerationPoemsData;
use Illuminate\Http\JsonResponse;

class ListModerationPoemsController extends Controller
{
    public function __construct(
        private readonly ListModerationPoemsAction $action,
    ) {
    }

    public function __invoke(ListModerationPoemsRequest $request): JsonResponse
    {
        $poems = $this->action->execute(ListModerationPoemsData::fromRequest($request));

        return ApiResponse::success(
            data: PoemResource::collection($poems->items()),
            meta: [
                'current_page' => $poems->currentPage(),
                'last_page' => $poems->lastPage(),
                'per_page' => $poems->perPage(),
                'total' => $poems->total(),
            ]
        );
    }
}
