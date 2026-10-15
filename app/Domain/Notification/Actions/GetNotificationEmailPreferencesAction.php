<?php

namespace Domain\Notification\Actions;

use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;

class GetNotificationEmailPreferencesAction
{
    /**
     * @return array<string, bool>
     */
    public function execute(User $user): array
    {
        $optOuts = $user->notification_email_opt_outs ?? [];

        $preferences = [];
        foreach (NotificationCategory::cases() as $category) {
            $preferences[$category->value] = ! in_array($category->value, $optOuts, true);
        }

        return $preferences;
    }
}
