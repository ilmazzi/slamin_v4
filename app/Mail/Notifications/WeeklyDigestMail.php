<?php

namespace App\Mail\Notifications;

use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WeeklyDigestMail extends NotificationCategoryMail
{
    public function __construct(
        User $recipient,
        public readonly array $popular,
        public readonly array $notifications,
    ) {
        parent::__construct($recipient, NotificationCategory::WeeklyDigest);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('notifications.mail.digest.subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.weekly-digest');
    }
}
