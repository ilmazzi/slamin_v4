<?php

namespace Domain\Admin\Actions;

use Domain\Admin\Models\MailSetting;
use Illuminate\Support\Facades\Mail;

class UpdateMailSettingsAction
{
    public function __construct(
        private readonly ApplyMailSettingsAction $applyMailSettingsAction,
    ) {}

    public function execute(array $data): MailSetting
    {
        $settings = MailSetting::query()->firstOrNew();

        if (($data['password'] ?? null) === null || $data['password'] === '') {
            unset($data['password']);
        }

        $settings->fill($data);
        $settings->save();

        $this->applyMailSettingsAction->execute();
        Mail::purge('smtp');

        return $settings;
    }
}
