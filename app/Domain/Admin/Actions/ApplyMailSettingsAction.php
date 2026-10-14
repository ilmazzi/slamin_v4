<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ApplyMailSettingsAction
{
    public const CACHE_KEY = 'mail_settings.attributes';

    public function execute(): ?MailSetting
    {
        $settings = $this->resolve();

        if (! $settings?->enabled || ! $settings->host || ! $settings->from_address) {
            return $settings;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $settings->scheme ?: null,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username ?: null,
            'mail.mailers.smtp.password' => $settings->password ?: null,
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name ?: config('app.name'),
        ]);

        return $settings;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function resolve(): ?MailSetting
    {
        $attributes = Cache::rememberForever(self::CACHE_KEY, function (): array {
            if (! Schema::hasTable('mail_settings')) {
                return [];
            }

            return MailSetting::query()->first()?->getAttributes() ?? [];
        });

        return $attributes === [] ? null : (new MailSetting)->newFromBuilder($attributes);
    }
}
