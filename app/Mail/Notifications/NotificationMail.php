<?php

namespace App\Mail\Notifications;

use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Models\Notification;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificationMail extends NotificationCategoryMail
{
    public function __construct(
        User $recipient,
        NotificationCategory $category,
        public readonly Notification $notification,
    ) {
        parent::__construct($recipient, $category);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notification->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.single',
            with: ['actionUrl' => NotificationMailLinks::forData($this->notification->data ?? [])],
        );
    }
}
