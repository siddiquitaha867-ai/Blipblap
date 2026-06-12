<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New BlipBlap contact request: ' . $this->data['topic'],
            replyTo: [
                new Address($this->data['email'], $this->data['name']),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-request',
        );
    }
}
