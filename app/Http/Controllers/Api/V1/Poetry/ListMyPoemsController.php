<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\ListMyPoemsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMyPoemsController extends Controller
{
    public function __construct(
        private readonly ListMyPoemsAction $listMyPoemsAction,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) $request->query('per_page', 10);

        $poems = $this->listMyPoemsAction->execute($user->id, $perPage);

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
