<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\StorePoemTypeRequest;
use App\Http\Resources\Api\V1\Poetry\AdminPoemTypeResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminStorePoemTypeAction;
use Illuminate\Http\JsonResponse;

class AdminStorePoemTypeController extends Controller
{
    public function __construct(
        private readonly AdminStorePoemTypeAction $action,
    ) {
    }

    public function __invoke(StorePoemTypeRequest $request): JsonResponse
    {
        $type = $this->action->execute($request->validated(), $request->user()->id);

        return ApiResponse::success([
            'type' => new AdminPoemTypeResource($type),
        ], status: 201);
    }
}
