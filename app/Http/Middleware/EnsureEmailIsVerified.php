<?php

namespace App\Http\Middleware;

use App\Support\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->hasVerifiedEmail()) {
            return ApiResponse::error(
                message: __('auth.email_not_verified'),
                status: 403
            );
        }

        return $next($request);
    }
}
