<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;

use Illuminate\Http\JsonResponse;
use Domain\Auth\Actions\LoginUserAction;
use Domain\Auth\DTOs\LoginUserData;
use App\Support\Responses\ApiResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $validated = $request->validated();


        $data = new LoginUserData(
            email: $validated['email'],
            password: $validated['password'],
        );

        $user = $action->execute($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
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
