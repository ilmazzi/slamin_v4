<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Support\Responses\ApiResponse;
use Domain\Profile\Actions\UpdateProfileAction;
use Domain\Profile\DTOs\UpdateProfileData;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $validated = $request->validated();

        $data = new UpdateProfileData(
            nickname: $validated['nickname'] ?? null,
            displayName: $validated['display_name'] ?? null,
            preferredLocale: $validated['preferred_locale'] ?? null,
        );

        $profile = $action->execute(
            $request->user()->profile,
            $data,
        );

        return ApiResponse::success([
            'profile' => [
                'public_id' => $profile->public_id,
                'nickname' => $profile->nickname,
                'display_name' => $profile->display_name,
                'preferred_locale' => $profile->preferred_locale,
            ],
        ]);
    }
}
