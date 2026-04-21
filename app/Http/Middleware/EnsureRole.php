<?php

namespace App\Http\Middleware;

use App\Support\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: 401
            );
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return ApiResponse::error(
            message: __('auth.insufficient_role'),
            status: 403
        );
    }
}
