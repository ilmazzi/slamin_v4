<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Support\Responses\ApiResponse;
use Domain\Auth\Actions\RegisterUserAction;
use Domain\Auth\DTOs\RegisterUserData;
use Domain\Auth\Support\AuthUserPayload;
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
            'user' => AuthUserPayload::from($result['user']),
        ], status: 201);
    }
}
