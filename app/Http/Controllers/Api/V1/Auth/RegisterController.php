<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\RegisterUserAction;
use Domain\Auth\DTOs\RegisterUserData;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $validated = $request->validated();

        $data = RegisterUserData::fromRequest($request);

        $result = $action->execute($data);

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => [
                'public_id' => $result['user']->public_id,
                'email' => $result['user']->email,
                'status' => $result['user']->status,
                'profile' => [
                    'public_id' => $result['user']->profile->public_id,
                    'nickname' => $result['user']->profile->nickname,
                    'display_name' => $result['user']->profile->display_name,
                    'preferred_locale' => $result['user']->profile->preferred_locale,
                ],
            ],
        ], status: 201);
    }
}
