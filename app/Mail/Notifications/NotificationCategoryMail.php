<?php

namespace App\Mail\Notifications;

use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;

abstract class NotificationCategoryMail extends Mailable
{
    public readonly string $unsubscribeUrl;

    public readonly string $preferencesUrl;

    public function __construct(
        public readonly User $recipient,
        public readonly NotificationCategory $category,
    ) {
        $this->unsubscribeUrl = NotificationMailLinks::unsubscribe($recipient, $category);
        $this->preferencesUrl = NotificationMailLinks::preferences();
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
}
