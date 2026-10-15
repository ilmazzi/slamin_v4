<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;

class UpdateNotificationEmailPreferencesAction
{
    /**
     * @param  array<string, bool>  $changes
     */
    public function execute(User $user, array $changes): void
    {
        $optOuts = collect($user->notification_email_opt_outs ?? []);

        foreach ($changes as $key => $enabled) {
            $category = NotificationCategory::from($key);
            $optOuts = $enabled
                ? $optOuts->reject(fn (string $value) => $value === $category->value)
                : $optOuts->push($category->value);
        }

        $user->forceFill([
            'notification_email_opt_outs' => $optOuts->unique()->values()->all(),
        ])->save();
    }
}
