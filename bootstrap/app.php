<?php

use App\Http\Middleware\EnsureAccountType;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use App\Providers\AuthServiceProvider;
use App\Support\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias([
            'account.verified' => EnsureEmailIsVerified::class,
            'account.type' => EnsureAccountType::class,
            'role' => EnsureRole::class,
        ]);

        $middleware->api(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: $exception->getMessage(),
                    fields: $exception->errors(),
                    status: $exception->status,
                );
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    message: $exception->getMessage(),
                    status: 403,
                );
            }

            return null;
        });
    })->create();
