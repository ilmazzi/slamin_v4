<?php

namespace App\Mail;

use Domain\Event\Models\Event;
use Domain\Event\Models\EventAudienceInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventExternalAudienceInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly EventAudienceInvite $invite,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('event.mail.external_audience_invite_subject', [
                'title' => $this->event->title,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.event-external-audience-invite');
    }
}
