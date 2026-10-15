<?php

namespace App\Mail\Notifications;

use Domain\Notification\Enums\NotificationCategory;
use Domain\User\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LikesSummaryMail extends NotificationCategoryMail
{
    /**
     * @param  list<array{title: string, url: string, count: int, names: list<string>}>  $items
     */
    public function __construct(
        User $recipient,
        public readonly array $items,
        public readonly int $total,
    ) {
        parent::__construct($recipient, NotificationCategory::Likes);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans_choice('notifications.mail.likes.subject', $this->total, ['count' => $this->total]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.likes-summary');
    }
}
