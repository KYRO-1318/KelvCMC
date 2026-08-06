<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KelvMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $view,
        public string $subjectText,
        public array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectText);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->view,
            with: ['mailData' => $this->data],
        );
    }
}
