<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminDeletePoemTypeAction;
use Domain\Poetry\Models\PoemType;
use Illuminate\Http\JsonResponse;

class AdminDeletePoemTypeController extends Controller
{
    public function __construct(
        private readonly AdminDeletePoemTypeAction $action,
    ) {
    }

    public function __invoke(PoemType $poemType): JsonResponse
    {
        if (! $this->action->execute($poemType, request()->user()->id)) {
            return ApiResponse::error(
                message: 'Cannot delete a type that is already used by one or more poems.',
                status: 422
            );
        }

        return ApiResponse::success();
    }
}
