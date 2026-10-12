<?php

namespace Domain\Media\Actions;

use Domain\Media\Models\MediaSetting;

class UpdateMediaSettingsAction
{
    /**
     * @param  array<string,mixed>  $settings
     */
    public function execute(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (! array_key_exists($key, ResolveMediaSettingsAction::DEFAULTS)) {
                continue;
            }

            if (
                in_array($key, ResolveMediaSettingsAction::SECRET_KEYS, true)
                && ($value === null || $value === '')
            ) {
                continue;
            }

            MediaSetting::setValue($key, $value);
        }
    }
}
