<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\MailSetting;

class ResolveMailSettingsAction
{
    public function execute(): array
    {
        $settings = MailSetting::query()->first();

        return [
            'enabled' => (bool) ($settings?->enabled ?? false),
            'host' => $settings?->host,
            'port' => $settings?->port ?? 587,
            'scheme' => $settings?->scheme,
            'username' => $settings?->username,
            'password' => null,
            'password_set' => filled($settings?->password),
            'from_address' => $settings?->from_address,
            'from_name' => $settings?->from_name,
        ];
    }
}
