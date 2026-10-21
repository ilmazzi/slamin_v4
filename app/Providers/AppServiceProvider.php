<?php

namespace App\Providers;

use App\Support\Responses\ApiResponse;
use Carbon\CarbonImmutable;
use Domain\Admin\Actions\ApplyMailSettingsAction;
use Domain\Audit\Actions\StoreAuditLogAction;
use Domain\Audit\Support\AuditLogger;
use Domain\I18n\Support\DatabaseTranslationLoader;
use Domain\Media\Actions\ResolveMediaSettingsAction;
use Domain\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger(
                storeAuditLogAction: $app->make(StoreAuditLogAction::class),
            );
        });

        $this->app->scoped(ResolveMediaSettingsAction::class);

        $this->app->extend('translation.loader', function ($fileLoader) {
            return new DatabaseTranslationLoader($fileLoader);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->configureDefaults();

        $this->app->beforeResolving('mail.manager', function ($abstract, $parameters, $app) {
            if ($app->resolved('mail.manager')) {
                return;
            }

            try {
                $app->make(ApplyMailSettingsAction::class)->execute();
            } catch (Throwable $exception) {
                report($exception);
            }
        });

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

            return rtrim(config('app.frontend_url'), '/').'/verify-email?'.http_build_query([
                'id' => $id,
                'hash' => $hash,
                'expires' => $expires,
                'signature' => $signature,
            ]);
        });

        VerifyEmail::toMailUsing(function (User $user, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('auth.verification_mail_subject'))
                ->view('emails.auth.verify-email', [
                    'user' => $user,
                    'verificationUrl' => $url,
                ]);
        });

        RateLimiter::for('update-password', function (Request $request) {
            return Limit::perMinute(5)
                ->by((string) ($request->user()?->id ?: $request->ip()))
                ->response(function () {
                    return ApiResponse::error(
                        message: __('auth.too_many_attempts'),
                        status: 429
                    );
                });
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
                ->by(mb_strtolower($email).'|'.$request->ip())
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
