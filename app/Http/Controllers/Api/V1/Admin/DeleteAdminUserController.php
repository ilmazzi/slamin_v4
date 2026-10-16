<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAdminUserController extends Controller
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => ['Non puoi eliminare il tuo account amministratore.'],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->profile?->delete();
            $user->delete();
        });

        return ApiResponse::success();
    }
}
