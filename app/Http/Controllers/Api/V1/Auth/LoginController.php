<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\LoginUserAction;
use Domain\Auth\DTOs\LoginUserData;
use Domain\Auth\Support\AuthUserPayload;
use Illuminate\Http\JsonResponse;

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
            'user' => AuthUserPayload::from($user),
        ]);
    }
}
