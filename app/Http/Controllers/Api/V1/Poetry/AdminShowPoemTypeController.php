<?php

namespace App\Http\Controllers\Api\V1\Poetry;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Poetry\AdminPoemTypeResource;
use App\Support\Responses\ApiResponse;
use Domain\Poetry\Models\PoemType;
use Illuminate\Http\JsonResponse;

class AdminShowPoemTypeController extends Controller
{
    public function __invoke(PoemType $poemType): JsonResponse
    {
        return ApiResponse::success([
            'type' => new AdminPoemTypeResource($poemType->load('translations')),
        ]);
    }
}
