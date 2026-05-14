<?php

namespace App\Services\EsimGo;

use App\Models\CustomerEsim;
use App\Models\EsimEvent;
use App\Models\EsimOrder;
use App\Models\EsimPlan;

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
            return $existing;
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
                'status' => 'provisioning_pending',
                'fulfillment_status' => 'pending_install_details',
                'response_payload' => $this->appendResponse($order, ['esim_go_order' => $response]),
            ]);

            EsimEvent::query()->create([
                'esim_order_id' => $order->id,
                'event_type' => 'provisioning_pending',
                'event_payload' => ['response' => $response],
            ]);

            return null;
        }

        $installDetails = $this->normalizeInstallDetails($assignment, $response);
        $installDetails['qr_code_url'] ??= $this->qrCodeFromInstallDetailsZip($reference);

        if (! $installDetails['activation_code'] && $installDetails['smdp_address'] && $installDetails['matching_id']) {
            $installDetails['activation_code'] = 'LPA:1$' . $installDetails['smdp_address'] . '$' . $installDetails['matching_id'];
        }

        if ($installDetails['qr_code_url'] && $installDetails['status'] === 'pending_install_details') {
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
            'status' => 'provisioned',
            'fulfillment_status' => 'ready_to_install',
            'response_payload' => $this->appendResponse($order, ['esim_go_order' => $response]),
        ]);

        EsimEvent::query()->create([
            'customer_esim_id' => $esim->id,
            'esim_order_id' => $order->id,
            'event_type' => 'ready_to_install',
            'event_payload' => [
                'iccid' => $iccid,
                'bundle_code' => $order->bundle_code,
            ],
        ]);

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
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
                    continue;
                }

                $image = $zip->getFromIndex($index);

                if ($image === false) {
                    continue;
                }

                $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode($image);
            }
        } finally {
            if ($opened) {
                $zip->close();
            }

            @unlink($path);
        }

        return null;
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
}
