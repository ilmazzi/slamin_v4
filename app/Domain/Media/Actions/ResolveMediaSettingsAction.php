<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaSetting;
use Illuminate\Support\Facades\Cache;

class ResolveMediaSettingsAction
{
    public const CACHE_KEY = 'media_settings.resolved';

    public const SECRET_KEYS = [
        'peertube_admin_password',
        'stripe_secret_key',
        'stripe_webhook_secret',
    ];

    public const DEFAULTS = [
        'photo_limit' => 50,
        'video_limit' => 3,
        'photo_max_size_kb' => 5120,
        'video_max_size_kb' => 102400,
        'photo_allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'video_allowed_mimes' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'],
        'peertube_base_url' => null,
        'peertube_admin_username' => null,
        'peertube_admin_password' => null,
        'peertube_upload_channel_id' => null,
        'peertube_default_privacy' => 1,
        'peertube_default_category' => 1,
        'peertube_default_licence' => 1,
        'peertube_comments_policy' => 1,
        'peertube_download_enabled' => true,
        'peertube_verify_ssl' => true,
        'stripe_secret_key' => null,
        'stripe_publishable_key' => null,
        'stripe_webhook_secret' => null,
        'stripe_checkout_success_url' => null,
        'stripe_checkout_cancel_url' => null,
        'stripe_api_base_url' => 'https://api.stripe.com',
    ];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolved = null;

    public function execute(): array
    {
        return $this->resolved ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->load());
    }

    public function flush(): void
    {
        $this->resolved = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentForAdmin(): array
    {
        $settings = $this->execute();

        foreach (self::SECRET_KEYS as $key) {
            $settings[$key.'_set'] = filled($settings[$key] ?? null);
            $settings[$key] = null;
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $stored = MediaSetting::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->get()
            ->keyBy('key');

        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $setting = $stored->get($key);
            $settings[$key] = $setting ? $setting->resolvedValue($default) : $default;
        }

        return $settings;
    }
}
