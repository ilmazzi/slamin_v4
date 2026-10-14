<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SmtpTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth.smtp_test_subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.smtp-test');
    }
}
