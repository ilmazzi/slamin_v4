<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\AdminPoemTypeResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\AdminListPoemTypesAction;
use Illuminate\Http\JsonResponse;

class AdminListPoemTypesController extends Controller
{
    public function __construct(
        private readonly AdminListPoemTypesAction $action,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'types' => AdminPoemTypeResource::collection($this->action->execute()),
        ]);
    }
}
