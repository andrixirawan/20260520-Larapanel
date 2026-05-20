<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthActivityNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $activity,
        public readonly string $userName,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
        public readonly CarbonImmutable $occurredAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Aktivitas akun: %s', $this->activity),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth-activity',
        );
    }
}
