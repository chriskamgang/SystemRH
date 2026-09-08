<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email portant le code a usage unique de connexion (3.1). */
class CodeConnexionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $dureeMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->code} — ton code de connexion INSAM BUS",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.code-connexion');
    }
}
