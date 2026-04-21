<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Domain\Auth\Actions\MeUserAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Responses\ApiResponse;
class MeController extends Controller
{
    public function __invoke(Request $request, MeUserAction $action): JsonResponse
    {
        $user = $action->execute($request->user());

        return ApiResponse::success([
            'user' => [
                'public_id' => $user->public_id,
                'email' => $user->email,
                'status' => $user->status,
                'profile' => [
                    'public_id' => $user->profile?->public_id,
                    'nickname' => $user->profile?->nickname,
                    'display_name' => $user->profile?->display_name,
                    'preferred_locale' => $user->profile?->preferred_locale,
                ],
            ],
        ]);
    }
}
