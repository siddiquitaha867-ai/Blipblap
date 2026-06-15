<?php

namespace App\Services;

use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Support\Carbon;

class EsimUsageService
{
    public function providerEsimData(EsimGoClient $client, CustomerEsim $esim): array
    {
        $data = [
            'esim' => [],
            'bundles' => [],
            'bundle_status' => [],
        ];

        if (! $esim->iccid) {
            return $data;
        }

        try {
            $data['esim'] = $client->refreshEsim($esim->iccid);
        } catch (\Throwable) {
            $data['esim'] = [];
        }

        try {
            $data['bundles'] = $client->appliedBundles($esim->iccid);
        } catch (\Throwable) {
            $data['bundles'] = [];
        }

        if ($esim->current_bundle_code) {
            try {
                $data['bundle_status'] = $client->appliedBundleStatus($esim->iccid, $esim->current_bundle_code);
            } catch (\Throwable) {
                $data['bundle_status'] = [];
            }
        }

        return $data;
    }

    public function summary(array $providerData, ?EsimPlan $plan): array
    {
        $remainingRaw = $this->firstValue($providerData, [
            'bundle_status.remainingData',
            'bundle_status.remaining_data',
            'bundle_status.data.remaining',
            'bundle_status.allowance.remaining',
            'bundle_status.bundle.remainingData',
            'esim.remainingData',
            'esim.remaining_data',
            'esim.data.remaining',
        ]);
        $totalRaw = $this->firstValue($providerData, [
            'bundle_status.totalData',
            'bundle_status.total_data',
            'bundle_status.data.total',
            'bundle_status.allowance.total',
            'bundle_status.bundle.totalData',
            'esim.totalData',
            'esim.total_data',
        ]);
        $usedRaw = $this->firstValue($providerData, [
            'bundle_status.usedData',
            'bundle_status.used_data',
            'bundle_status.consumedData',
            'bundle_status.consumed_data',
            'bundle_status.data.used',
            'bundle_status.allowance.used',
            'esim.usedData',
            'esim.used_data',
            'esim.data.used',
        ]);

        $totalMb = $this->dataToMegabytes($totalRaw) ?? $this->planTotalMegabytes($plan);
        $remainingMb = $this->dataToMegabytes($remainingRaw);
        $usedMb = $this->dataToMegabytes($usedRaw);

        if ($usedMb === null && $totalMb !== null && $remainingMb !== null) {
            $usedMb = max(0, $totalMb - $remainingMb);
        }

        if ($remainingMb === null && $totalMb !== null && $usedMb !== null) {
            $remainingMb = max(0, $totalMb - $usedMb);
        }

        $usagePercent = null;
        $remainingPercent = null;

        if ($totalMb !== null && $totalMb > 0 && $usedMb !== null) {
            $usagePercent = round(min(100, max(0, ($usedMb / $totalMb) * 100)));
            $remainingPercent = 100 - $usagePercent;
        }

        $remainingDays = $this->daysRemaining($this->firstValue($providerData, [
            'bundle_status.expiryDate',
            'bundle_status.expiry_date',
            'bundle_status.expiresAt',
            'bundle_status.expires_at',
            'esim.expiryDate',
            'esim.expiresAt',
        ]), $plan?->duration_days);

        return [
            'days_remaining' => $remainingDays,
            'used_data' => $this->formatMegabytes($usedMb),
            'remaining_data' => $this->formatMegabytes($remainingMb) ?? ($plan?->unlimited ? 'Unlimited' : null),
            'total_data' => $this->formatMegabytes($totalMb) ?? ($plan?->unlimited ? 'Unlimited' : null),
            'usage_percent' => $usagePercent,
            'remaining_percent' => $remainingPercent,
            'usage_status' => $this->firstValue($providerData, [
                'bundle_status.status',
                'esim.status',
            ]) ?: 'Unknown',
        ];
    }

    public function firstValue(array $source, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function planTotalMegabytes(?EsimPlan $plan): ?float
    {
        if (! $plan || $plan->unlimited || ! is_numeric($plan->data_amount)) {
            return null;
        }

        return $this->dataToMegabytes($plan->data_amount . ' ' . ($plan->data_unit ?: 'GB'));
    }

    private function dataToMegabytes(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = strtoupper(trim((string) $value));

        if (! preg_match('/([0-9]+(?:\.[0-9]+)?)\s*(TB|GB|MB|KB)?/', $text, $matches)) {
            return null;
        }

        $amount = (float) $matches[1];
        $unit = $matches[2] ?? 'MB';

        return match ($unit) {
            'TB' => $amount * 1024 * 1024,
            'GB' => $amount * 1024,
            'KB' => $amount / 1024,
            default => $amount,
        };
    }

    private function formatMegabytes(?float $megabytes): ?string
    {
        if ($megabytes === null) {
            return null;
        }

        if ($megabytes >= 1024 * 1024) {
            return round($megabytes / 1024 / 1024, 2) . ' TB';
        }

        if ($megabytes >= 1024) {
            return round($megabytes / 1024, 2) . ' GB';
        }

        return round($megabytes, 2) . ' MB';
    }

    private function daysRemaining(mixed $dateValue, ?int $fallbackDuration): ?int
    {
        if (is_string($dateValue) && trim($dateValue) !== '') {
            try {
                return max(0, (int) now()->diffInDays(Carbon::parse($dateValue), false));
            } catch (\Throwable) {
                return $fallbackDuration;
            }
        }

        return $fallbackDuration;
    }
}
