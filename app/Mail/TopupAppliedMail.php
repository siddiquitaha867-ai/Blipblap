<?php

namespace App\Mail;

use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TopupAppliedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly CustomerEsim $esim,
        public readonly EsimOrder $order,
        public readonly ?EsimPlan $plan = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BlipBlap eSIM top-up is active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.topup-applied',
        );
    }
}
