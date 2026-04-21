<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\StorePoemRequest;
use App\Http\Resources\Api\V1\Poetry\PoemResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\StorePoemAction;
use Domain\Poetry\DTOs\StorePoemData;
use Illuminate\Http\JsonResponse;

class StorePoemController extends Controller
{
    public function __construct(
        private readonly StorePoemAction $storePoemAction,
    ) {
    }

    public function __invoke(StorePoemRequest $request): JsonResponse
    {
        $data = StorePoemData::fromRequest($request);

        $poem = $this->storePoemAction->execute($data);

        return ApiResponse::success([
            'poem' => new PoemResource($poem),
        ]);
    }
}
