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

class EsimReadyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public ?string $qrImageData = null;

    public string $qrMime = 'image/png';

    public function __construct(
        public readonly CustomerEsim $esim,
        public readonly EsimOrder $order,
        public readonly ?EsimPlan $plan = null,
    ) {
        $this->prepareQrImage();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BlipBlap eSIM is ready to install',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.esim-ready',
        );
    }

    private function prepareQrImage(): void
    {
        $qrCodeUrl = (string) $this->esim->qr_code_url;

        if (! str_starts_with($qrCodeUrl, 'data:image/')) {
            return;
        }

        [$meta, $payload] = array_pad(explode(',', $qrCodeUrl, 2), 2, '');

        if ($payload === '') {
            return;
        }

        $mime = str($meta)
            ->after('data:')
            ->before(';')
            ->toString();

        $decoded = base64_decode($payload, true);

        if (! $decoded) {
            return;
        }

        $this->qrMime = $mime !== '' ? $mime : 'image/png';
        $this->qrImageData = $decoded;
    }
}
