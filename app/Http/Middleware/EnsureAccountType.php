<?php

namespace App\Http\Middleware;

use App\Support\Responses\ApiResponse;
use Closure;
use Domain\User\Enums\AccountType;
use Illuminate\Http\Request;

class EnsureAccountType
{
    public function handle(Request $request, Closure $next, string $type)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: 401
            );
        }

        if ($user->account_type !== AccountType::from($type)) {
            return ApiResponse::error(
                message: __('auth.invalid_account_type'),
                status: 403
            );
        }

        return $next($request);
    }
}
