<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Support\Responses\ApiResponse;
use Domain\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Domain\Audit\Support\AuditLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger(
                storeAuditLogAction: $app->make(\Domain\Audit\Actions\StoreAuditLogAction::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->configureDefaults();
        RateLimiter::for('verification-notification', function (Request $request) {
            $userId = $request->user()?->getKey() ?? $request->ip();

            return Limit::perMinute(3)
                ->by((string) $userId)
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });
        RateLimiter::for('verify-email', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });

        VerifyEmail::createUrlUsing(function (User $user): string {
            $expires = now()->addMinutes(60)->timestamp;
            $id = (string) $user->getKey();
            $hash = sha1($user->getEmailForVerification());

            $signature = hash_hmac(
                'sha256',
                "{$id}|{$hash}|{$expires}",
                config('app.key')
            );

            return rtrim(config('app.frontend_url'), '/') . '/verify-email?' . http_build_query([
                'id' => $id,
                'hash' => $hash,
                'expires' => $expires,
                'signature' => $signature,
            ]);
        });

        RateLimiter::for('reset-password', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->string('email');

            return Limit::perMinute(5)
                ->by(mb_strtolower($email) . '|' . $request->ip())
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
        });

    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
