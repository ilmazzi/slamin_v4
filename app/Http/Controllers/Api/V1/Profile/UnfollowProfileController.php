<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\Engagement\Actions\UnfollowAction;
use Domain\Profile\Actions\ShowPublicProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnfollowProfileController extends Controller
{
    public function __construct(
        private readonly ShowPublicProfileAction $profiles,
        private readonly UnfollowAction $unfollowAction,
    ) {}

    public function __invoke(Request $request, string $nickname): JsonResponse
    {
        $profile = $this->profiles->execute($nickname);

        if (! $profile?->user) {
            return ApiResponse::error(message: (string) __('profile.errors.not_found'), status: 404);
        }

        return ApiResponse::success([
            'follow' => $this->unfollowAction->execute($profile->user, $request->user()->id),
        ]);
    }
}
