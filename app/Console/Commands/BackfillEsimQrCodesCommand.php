<?php

namespace App\Console\Commands;

use App\Models\CustomerEsim;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Console\Command;

class BackfillEsimQrCodesCommand extends Command
{
    protected $signature = 'esims:backfill-qr-codes';

    protected $description = 'Generate missing eSIM QR codes from saved activation codes.';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        CustomerEsim::query()
            ->where(function ($query): void {
                $query->whereNull('qr_code_url')
                    ->orWhere('qr_code_url', '');
            })
            ->whereNotNull('activation_code')
            ->orderBy('id')
            ->chunkById(100, function ($esims) use (&$updated, &$skipped): void {
                foreach ($esims as $esim) {
                    $qrCodeUrl = $this->qrCodeFromActivationCode((string) $esim->activation_code);

                    if (! $qrCodeUrl) {
                        $skipped++;

                        continue;
                    }

                    $esim->update([
                        'qr_code_url' => $qrCodeUrl,
                        'status' => 'ready_to_install',
                    ]);

                    $updated++;
                }
            });

        $this->info("QR backfill complete. Updated: {$updated}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function qrCodeFromActivationCode(string $activationCode): ?string
    {
        if (trim($activationCode) === '') {
            return null;
        }

        try {
            $renderer = new ImageRenderer(
                new RendererStyle(360, 2),
                new SvgImageBackEnd(),
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($activationCode);

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable) {
            return null;
        }
    }
}
