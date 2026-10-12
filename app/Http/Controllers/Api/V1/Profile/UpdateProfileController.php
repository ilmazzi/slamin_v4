<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Support\Responses\ApiResponse;
use Domain\Profile\Actions\UpdateProfileAction;
use Domain\Profile\DTOs\UpdateProfileData;
use Domain\Profile\Support\ProfilePayload;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $validated = $request->validated();

        $data = new UpdateProfileData(
            nickname: $validated['nickname'] ?? null,
            displayName: array_key_exists('display_name', $validated) ? $validated['display_name'] : null,
            preferredLocale: $validated['preferred_locale'] ?? null,
            bio: array_key_exists('bio', $validated) ? $validated['bio'] : null,
            location: array_key_exists('location', $validated) ? $validated['location'] : null,
            websiteUrl: array_key_exists('website_url', $validated) ? $validated['website_url'] : null,
            isPublic: array_key_exists('is_public', $validated) ? (bool) $validated['is_public'] : null,
            visibility: $validated['visibility'] ?? null,
            avatar: $request->file('avatar'),
            removeAvatar: (bool) ($validated['remove_avatar'] ?? false),
            banner: $request->file('banner'),
            removeBanner: (bool) ($validated['remove_banner'] ?? false),
            provided: array_keys($validated),
        );

        $profile = $request->user()->profile()->first();
        if ($profile === null) {
            return ApiResponse::error(
                message: (string) __('profile.errors.not_found'),
                status: 404,
            );
        }

        $action->execute($profile, $data);

        $user = $request->user()->fresh()->load(['profile', 'roles']);

        return ApiResponse::success([
            'profile' => ProfilePayload::owner($user),
        ]);
    }
}
