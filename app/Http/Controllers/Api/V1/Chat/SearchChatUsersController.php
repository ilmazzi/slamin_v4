<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SearchChatUsersController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = mb_strtolower(trim($validated['q']));

        $users = User::query()
            ->with('profile')
            ->where('status', 'active')
            ->whereNotNull('email_verified_at')
            ->whereKeyNot($request->user()->id)
            ->whereHas('profile', function ($profileQuery) use ($query): void {
                $profileQuery
                    ->where('is_public', true)
                    ->where(function ($nameQuery) use ($query): void {
                        $nameQuery
                            ->whereRaw('LOWER(nickname) LIKE ?', ["%{$query}%"])
                            ->orWhereRaw('LOWER(display_name) LIKE ?', ["%{$query}%"]);
                    });
            })
            ->limit(10)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'nickname' => $user->profile?->nickname,
                'display_name' => $user->profile?->display_name,
                'avatar_url' => $user->profile?->avatar_path
                    ? Storage::disk('public')->url($user->profile->avatar_path)
                    : null,
            ])
            ->values();

        return ApiResponse::success([
            'users' => $users,
        ]);
    }
}
