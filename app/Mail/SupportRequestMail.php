<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public readonly string $requesterName,
        public readonly string $requesterEmail,
        public readonly string $category,
        public readonly string $requestSubject,
        public readonly string $requestMessage,
        public readonly array $files = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->requesterEmail, $this->requesterName)],
            subject: __('support.mail_subject', ['subject' => $this->requestSubject]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.support-request');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return collect($this->files)
            ->map(function (UploadedFile $file): ?Attachment {
                $path = $file->getRealPath();
                if (! is_string($path)) {
                    return null;
                }

                return Attachment::fromPath($path)
                    ->as($file->getClientOriginalName())
                    ->withMime($file->getMimeType() ?: 'application/octet-stream');
            })
            ->filter()
            ->values()
            ->all();
    }
}
