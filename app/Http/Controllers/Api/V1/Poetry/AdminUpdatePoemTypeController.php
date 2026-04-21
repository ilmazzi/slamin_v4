<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Poetry\UpdatePoemTypeRequest;
use App\Http\Resources\Api\V1\Poetry\AdminPoemTypeResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminUpdatePoemTypeAction;
use Domain\Poetry\Models\PoemType;
use Illuminate\Http\JsonResponse;

class AdminUpdatePoemTypeController extends Controller
{
    public function __construct(
        private readonly AdminUpdatePoemTypeAction $action,
    ) {
    }

    public function __invoke(UpdatePoemTypeRequest $request, PoemType $poemType): JsonResponse
    {
        $type = $this->action->execute($poemType, $request->validated(), $request->user()->id);

        return ApiResponse::success([
            'type' => new AdminPoemTypeResource($type),
        ]);
    }
}
