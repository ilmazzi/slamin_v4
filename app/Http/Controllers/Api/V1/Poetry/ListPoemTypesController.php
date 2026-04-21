<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\PoemTypeResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Actions\ListPoemTypesAction;
use Illuminate\Http\JsonResponse;

class ListPoemTypesController extends Controller
{
    public function __construct(
        private readonly ListPoemTypesAction $listPoemTypesAction,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $types = $this->listPoemTypesAction->execute();

        return ApiResponse::success([
            'types' => PoemTypeResource::collection($types),
        ]);
    }
}
