<?php

namespace App\Services\EsimGo;

use App\Mail\EsimReadyMail;
use App\Mail\TopupAppliedMail;
use App\Models\CustomerEsim;
use App\Models\EsimEvent;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Mail;

class OrderProvisioningService
{
    public function __construct(private readonly EsimGoClient $client)
    {
    }

    public function provision(EsimOrder $order, ?EsimPlan $plan = null): ?CustomerEsim
    {
        $existing = CustomerEsim::query()
            ->where('source_order_id', $order->id)
            ->first();

        if ($existing) {
            return $this->completeExistingInstallDetails($existing, $order, $plan);
        }

        if (! in_array($order->status, ['paid', 'provisioning', 'provisioned'], true)) {
            return null;
        }

        $order->update([
            'status' => 'provisioning',
            'fulfillment_status' => 'provisioning',
        ]);

        $payload = $this->buildOrderPayload($order);
        $response = $this->client->createOrder($payload);
        $assignment = $this->assignmentFrom($response);
        $reference = $this->value($response, ['orderReference', 'order_reference', 'reference', 'id'])
            ?: (string) $order->order_reference;

        if (! $this->value($assignment, ['iccid', 'esim.iccid'])) {
            if ($reference !== '') {
                $detailsResponse = $this->client->installDetails($reference);
                $assignment = $this->assignmentFrom($detailsResponse) ?: $assignment;
                $response['install_details_response'] = $detailsResponse;
            }
        }

        $iccid = $this->value($assignment, ['iccid', 'esim.iccid']);

        if (! $iccid) {
            $order->update([
                'apply_reference' => $reference ?: $order->apply_reference,
                'status' => 'provisioning_pending',
                'fulfillment_status' => 'pending_install_details',
                'response_payload' => $this->appendResponse($order, [
                    'esim_go_order' => $response,
                    'esim_go_lookup_reference' => $reference ?: $order->order_reference,
                ]),
            ]);

            EsimEvent::query()->create([
                'esim_order_id' => $order->id,
                'event_type' => 'provisioning_pending',
                'event_payload' => [
                    'blipblap_reference' => $order->order_reference,
                    'esim_go_reference' => $reference ?: $order->order_reference,
                    'response' => $response,
                ],
            ]);

            return null;
        }

        $installDetails = $this->normalizeInstallDetails($assignment, $response);
        $installDetails['qr_code_url'] ??= $this->qrCodeFromInstallDetailsZip($reference);

        if (! $installDetails['activation_code'] && $installDetails['smdp_address'] && $installDetails['matching_id']) {
            $installDetails['activation_code'] = 'LPA:1$' . $installDetails['smdp_address'] . '$' . $installDetails['matching_id'];
        }

        if (! $installDetails['qr_code_url'] && $installDetails['activation_code']) {
            $installDetails['qr_code_url'] = $this->qrCodeFromActivationCode($installDetails['activation_code']);
        }

        if (($installDetails['qr_code_url'] || $installDetails['activation_code']) && $installDetails['status'] === 'pending_install_details') {
            $installDetails['status'] = 'ready_to_install';
        }

        $esim = CustomerEsim::query()->updateOrCreate(
            ['iccid' => $iccid],
            [
                'user_id' => $order->user_id,
                'customer_email' => $order->customer_email,
                'nickname' => $plan?->title,
                'current_bundle_code' => $order->bundle_code,
                'status' => $installDetails['status'],
                'matching_id' => $installDetails['matching_id'],
                'smdp_address' => $installDetails['smdp_address'],
                'activation_code' => $installDetails['activation_code'],
                'qr_code_url' => $installDetails['qr_code_url'],
                'install_details' => $installDetails['raw'],
                'last_status' => $response,
                'topup_supported' => (bool) ($plan?->topup_supported ?? false),
                'expires_at' => $installDetails['expires_at'],
                'last_synced_at' => now(),
                'source_order_id' => $order->id,
            ],
        );

        $order->update([
            'iccid' => $iccid,
            'apply_reference' => $reference ?: $order->apply_reference,
            'status' => 'provisioned',
            'fulfillment_status' => 'ready_to_install',
            'response_payload' => $this->appendResponse($order, [
                'esim_go_order' => $response,
                'esim_go_lookup_reference' => $reference ?: $order->order_reference,
            ]),
        ]);

        EsimEvent::query()->create([
            'customer_esim_id' => $esim->id,
            'esim_order_id' => $order->id,
            'event_type' => 'ready_to_install',
            'event_payload' => [
                'iccid' => $iccid,
                'bundle_code' => $order->bundle_code,
                'blipblap_reference' => $order->order_reference,
                'esim_go_reference' => $reference ?: $order->order_reference,
            ],
        ]);

        $this->sendReadyEmail($esim, $order, $plan);

        return $esim;
    }

    public function applyTopup(EsimOrder $order, ?EsimPlan $plan = null): ?CustomerEsim
    {
        if ($order->order_type !== 'topup') {
            return null;
        }

        $esim = CustomerEsim::query()
            ->where('iccid', $order->iccid)
            ->first();

        if (! $esim || ! $esim->topup_supported) {
            $order->update([
                'status' => 'topup_failed',
                'fulfillment_status' => 'topup_not_available',
                'response_payload' => $this->appendResponse($order, [
                    'topup_error' => 'The selected eSIM is not available for top-up.',
                ]),
            ]);

            return null;
        }

        if ($order->status === 'topup_applied' || $order->fulfillment_status === 'topup_applied') {
            return $esim;
        }

        if (! in_array($order->status, ['paid', 'provisioning'], true)) {
            return null;
        }

        $order->update([
            'status' => 'provisioning',
            'fulfillment_status' => 'topup_processing',
        ]);

        $response = $this->client->applyBundle([
            'iccid' => $order->iccid,
            'name' => $order->bundle_code,
            'allowReassign' => false,
        ]);

        $appliedIccid = $this->value($response, ['iccid', 'esims.0.iccid']) ?: $order->iccid;
        $applyReference = $this->value($response, ['applyReference', 'apply_reference', 'esims.0.applyReference', 'esims.0.apply_reference']);
        $providerStatus = strtolower((string) ($this->value($response, ['status', 'esims.0.status']) ?: 'topup_applied'));
        $esimStatus = str_contains($providerStatus, 'process') || str_contains($providerStatus, 'pending')
            ? 'topup_processing'
            : 'topup_applied';

        $esim->update([
            'current_bundle_code' => $order->bundle_code,
            'status' => $esimStatus,
            'last_status' => $response,
            'topup_supported' => (bool) ($plan?->topup_supported ?? $esim->topup_supported),
            'last_synced_at' => now(),
        ]);

        $order->update([
            'iccid' => $appliedIccid,
            'apply_reference' => $applyReference ?: $order->apply_reference,
            'status' => $esimStatus,
            'fulfillment_status' => $esimStatus,
            'response_payload' => $this->appendResponse($order, [
                'topup_apply' => $response,
                'esim_go_lookup_reference' => $applyReference ?: $order->order_reference,
            ]),
        ]);

        EsimEvent::query()->create([
            'customer_esim_id' => $esim->id,
            'esim_order_id' => $order->id,
            'event_type' => $esimStatus,
            'event_payload' => [
                'iccid' => $appliedIccid,
                'bundle_code' => $order->bundle_code,
                'blipblap_reference' => $order->order_reference,
                'apply_reference' => $applyReference,
            ],
        ]);

        if ($esimStatus === 'topup_applied') {
            $this->sendTopupEmail($esim->refresh(), $order->refresh(), $plan);
        }

        return $esim;
    }

    private function buildOrderPayload(EsimOrder $order): array
    {
        return [
            'type' => 'transaction',
            'assign' => true,
            'reference' => $order->order_reference,
            'order' => [
                [
                    'type' => 'bundle',
                    'quantity' => 1,
                    'item' => $order->bundle_code,
                ],
            ],
        ];
    }

    private function assignmentFrom(array $response): array
    {
        $candidates = [
            'esimAssignments.0',
            'eSIMAssignments.0',
            'assignments.0',
            'order.0.esims.0',
            'data.esimAssignments.0',
            'data.assignments.0',
            'data.order.0.esims.0',
            'esims.0',
            'data.0',
            '0',
        ];

        foreach ($candidates as $path) {
            $assignment = data_get($response, $path);

            if (is_array($assignment)) {
                return $assignment;
            }
        }

        return $response;
    }

    private function normalizeInstallDetails(array $assignment, array $response): array
    {
        $activationCode = $this->value($assignment, [
            'activationCode',
            'activation_code',
            'lpa',
            'lpaCode',
            'esim.activationCode',
        ]);
        $qrCodeUrl = $this->qrCodeUrl($assignment);

        return [
            'matching_id' => $this->value($assignment, ['matchingId', 'matching_id', 'matchingID', 'esim.matchingId', 'esim.matching_id']),
            'smdp_address' => $this->value($assignment, ['smdpAddress', 'smdp_address', 'smdp', 'address', 'esim.smdpAddress', 'esim.smdp_address']),
            'activation_code' => $activationCode,
            'qr_code_url' => $qrCodeUrl,
            'expires_at' => $this->value($assignment, ['expiresAt', 'expires_at', 'expiryDate']),
            'status' => $this->value($assignment, ['status']) ?: ($activationCode || $qrCodeUrl ? 'ready_to_install' : 'pending_install_details'),
            'raw' => [
                'assignment' => $assignment,
                'response' => $response,
            ],
        ];
    }

    private function qrCodeUrl(array $assignment): ?string
    {
        $qrCode = $this->value($assignment, ['qrCodeUrl', 'qr_code_url', 'qrcode_url', 'qr.url', 'esim.qrCodeUrl', 'esim.qr_code_url']);

        if ($qrCode) {
            return $qrCode;
        }

        $inlineCode = $this->value($assignment, ['qrCode', 'qr_code', 'qrcode', 'qr', 'esim.qrCode', 'esim.qr_code']);

        if (! $inlineCode) {
            return null;
        }

        if (str_starts_with($inlineCode, 'data:image/')) {
            return $inlineCode;
        }

        if (str_starts_with($inlineCode, 'http://') || str_starts_with($inlineCode, 'https://')) {
            return $inlineCode;
        }

        if (str_starts_with(ltrim($inlineCode), '<svg')) {
            return 'data:image/svg+xml;base64,' . base64_encode($inlineCode);
        }

        if (! preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $inlineCode)) {
            return null;
        }

        return 'data:image/png;base64,' . $inlineCode;
    }

    private function qrCodeFromInstallDetailsZip(?string $reference): ?string
    {
        if (! $reference || ! class_exists(\ZipArchive::class)) {
            return null;
        }

        try {
            $zipBody = $this->client->installDetailsZip($reference);
        } catch (\Throwable) {
            return null;
        }

        $rawImage = $this->imageDataUriFromBinary($zipBody);

        if ($rawImage) {
            return $rawImage;
        }

        $json = json_decode($zipBody, true);

        if (is_array($json)) {
            return $this->qrCodeUrl($json);
        }

        $path = tempnam(sys_get_temp_dir(), 'bb-esim-qr-');

        if ($path === false) {
            return null;
        }

        file_put_contents($path, $zipBody);

        $zip = new \ZipArchive();
        $opened = false;

        try {
            if ($zip->open($path) !== true) {
                return null;
            }

            $opened = true;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);

                if (str_ends_with($name, '/')) {
                    continue;
                }

                $image = $zip->getFromIndex($index);

                if ($image === false) {
                    continue;
                }

                $dataUri = $this->imageDataUriFromBinary($image);

                if ($dataUri) {
                    return $dataUri;
                }
            }
        } finally {
            if ($opened) {
                $zip->close();
            }

            @unlink($path);
        }

        return null;
    }

    private function imageDataUriFromBinary(string $binary): ?string
    {
        $mime = match (true) {
            str_starts_with($binary, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($binary, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with(ltrim($binary), '<svg') => 'image/svg+xml',
            default => null,
        };

        if (! $mime) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function completeExistingInstallDetails(CustomerEsim $esim, EsimOrder $order, ?EsimPlan $plan): CustomerEsim
    {
        $updates = [];

        if (! $esim->activation_code && $esim->smdp_address && $esim->matching_id) {
            $updates['activation_code'] = 'LPA:1$' . $esim->smdp_address . '$' . $esim->matching_id;
        }

        $activationCode = $updates['activation_code'] ?? $esim->activation_code;

        if (! $esim->qr_code_url) {
            $references = array_filter(array_unique([
                data_get($order->response_payload, 'esim_go_order.orderReference'),
                data_get($order->response_payload, 'esim_go_order.order_reference'),
                data_get($order->response_payload, 'esim_go_order.reference'),
                data_get($esim->last_status, 'orderReference'),
                data_get($esim->last_status, 'order_reference'),
                $order->order_reference,
            ]));

            foreach ($references as $reference) {
                $qrCodeUrl = $this->qrCodeFromInstallDetailsZip((string) $reference);

                if ($qrCodeUrl) {
                    $updates['qr_code_url'] = $qrCodeUrl;
                    break;
                }
            }

            if (! isset($updates['qr_code_url']) && $activationCode) {
                $updates['qr_code_url'] = $this->qrCodeFromActivationCode($activationCode);
            }
        }

        if (($updates['qr_code_url'] ?? $esim->qr_code_url) || ($updates['activation_code'] ?? $esim->activation_code)) {
            $updates['status'] = 'ready_to_install';
        }

        if ($updates !== []) {
            $esim->update($updates);
            $esim->refresh();
        }

        $this->sendReadyEmail($esim, $order, $plan);

        return $esim;
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

    private function value(array $source, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function appendResponse(EsimOrder $order, array $data): array
    {
        $current = is_array($order->response_payload) ? $order->response_payload : [];

        return array_merge($current, $data);
    }

    private function sendReadyEmail(CustomerEsim $esim, EsimOrder $order, ?EsimPlan $plan): void
    {
        $alreadySent = EsimEvent::query()
            ->where('customer_esim_id', $esim->id)
            ->where('esim_order_id', $order->id)
            ->where('event_type', 'ready_email_sent')
            ->exists();

        if ($alreadySent) {
            return;
        }

        try {
            Mail::to($order->customer_email)->send(new EsimReadyMail($esim, $order, $plan));

            EsimEvent::query()->create([
                'customer_esim_id' => $esim->id,
                'esim_order_id' => $order->id,
                'event_type' => 'ready_email_sent',
                'event_payload' => [
                    'to' => $order->customer_email,
                ],
            ]);
        } catch (\Throwable $exception) {
            EsimEvent::query()->create([
                'customer_esim_id' => $esim->id,
                'esim_order_id' => $order->id,
                'event_type' => 'ready_email_failed',
                'event_payload' => [
                    'to' => $order->customer_email,
                    'error' => $exception->getMessage(),
                ],
            ]);
        }
    }

    private function sendTopupEmail(CustomerEsim $esim, EsimOrder $order, ?EsimPlan $plan): void
    {
        $alreadySent = EsimEvent::query()
            ->where('customer_esim_id', $esim->id)
            ->where('esim_order_id', $order->id)
            ->where('event_type', 'topup_email_sent')
            ->exists();

        if ($alreadySent) {
            return;
        }

        try {
            Mail::to($order->customer_email)->send(new TopupAppliedMail($esim, $order, $plan));

            EsimEvent::query()->create([
                'customer_esim_id' => $esim->id,
                'esim_order_id' => $order->id,
                'event_type' => 'topup_email_sent',
                'event_payload' => [
                    'to' => $order->customer_email,
                ],
            ]);
        } catch (\Throwable $exception) {
            EsimEvent::query()->create([
                'customer_esim_id' => $esim->id,
                'esim_order_id' => $order->id,
                'event_type' => 'topup_email_failed',
                'event_payload' => [
                    'to' => $order->customer_email,
                    'error' => $exception->getMessage(),
                ],
            ]);
        }
    }
}
