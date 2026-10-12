<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\MeUserAction;
use Domain\Auth\Support\AuthUserPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request, MeUserAction $action): JsonResponse
    {
        $user = $action->execute($request->user());

        return ApiResponse::success([
            'user' => AuthUserPayload::from($user),
        ]);
    }
}
