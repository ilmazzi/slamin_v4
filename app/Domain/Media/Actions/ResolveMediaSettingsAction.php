<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaSetting;

class ResolveMediaSettingsAction
{
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

    public function execute(): array
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = MediaSetting::getValue($key, $default);
        }

        return $settings;
    }
}
