<?php

namespace App\Mail\Notifications;

use Domain\Chat\Models\ChatMessage;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class ChatMessageMail extends NotificationCategoryMail
{
    public readonly string $senderName;

    public function __construct(
        User $recipient,
        public readonly ChatMessage $message,
    ) {
        parent::__construct($recipient, NotificationCategory::ChatMessages);

        $profile = $message->user?->profile;
        $this->senderName = $profile?->display_name ?: ($profile?->nickname ?? '');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('notifications.mail.chat.subject', ['name' => $this->senderName]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.chat-message',
            with: [
                'excerpt' => Str::limit(trim((string) $this->message->content), 300),
                'threadTitle' => $this->message->thread?->isGroup() ? $this->message->thread->title : null,
                'actionUrl' => NotificationMailLinks::frontend('chat/'.$this->message->chat_thread_id),
            ],
        );
    }
}
