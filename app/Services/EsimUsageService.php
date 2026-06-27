<?php

namespace App\Services;

use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Support\Arr;
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

        $this->storeProviderSnapshot($esim, $data);

        return $data;
    }

    public function summary(array $providerData, ?EsimPlan $plan): array
    {
        $assignmentUsage = $this->assignmentUsage($providerData);
        $remainingRaw = $this->firstValue($providerData, [
            'bundle_status.remainingQuantity',
            'bundle_status.allowances.0.remainingAmount',
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
            'bundle_status.initialQuantity',
            'bundle_status.allowances.0.initialAmount',
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

        if ($assignmentUsage) {
            $totalMb = $assignmentUsage['total_mb'] ?? $totalMb;
            $remainingMb = $assignmentUsage['remaining_mb'] ?? $remainingMb;
            $usedMb = $assignmentUsage['used_mb'] ?? $usedMb;
        }

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
            'bundle_status.assignments.0.endTime',
            'bundle_status.endTime',
            'bundle_status.expiryDate',
            'bundle_status.expiry_date',
            'bundle_status.expiresAt',
            'bundle_status.expires_at',
            'esim.expiryDate',
            'esim.expiresAt',
        ]), $plan?->duration_days);

        if ($assignmentUsage && $assignmentUsage['end_time']) {
            $remainingDays = $this->daysRemaining($assignmentUsage['end_time'], $remainingDays);
        }

        $unlimited = (bool) ($assignmentUsage['unlimited'] ?? false) || (bool) $plan?->unlimited;

        return [
            'days_remaining' => $remainingDays,
            'used_data' => $this->formatMegabytes($usedMb),
            'remaining_data' => $this->formatMegabytes($remainingMb) ?? ($unlimited ? 'Unlimited' : null),
            'total_data' => $this->formatMegabytes($totalMb) ?? ($unlimited ? 'Unlimited' : null),
            'usage_percent' => $usagePercent,
            'remaining_percent' => $remainingPercent,
            'usage_status' => ($assignmentUsage['status'] ?? null) ?: $this->firstValue($providerData, [
                'bundle_status.assignments.0.bundleState',
                'bundle_status.bundleState',
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

    private function assignmentUsage(array $providerData): ?array
    {
        $assignments = data_get($providerData, 'bundle_status.assignments');

        if (! is_array($assignments)) {
            $single = data_get($providerData, 'bundle_status');
            $assignments = is_array($single) && array_key_exists('initialQuantity', $single) ? [$single] : [];
        }

        $assignments = array_values(array_filter($assignments, fn ($assignment): bool => is_array($assignment)));

        if ($assignments === []) {
            return null;
        }

        $totalMb = 0.0;
        $remainingMb = 0.0;
        $hasMeasuredData = false;
        $unlimited = false;
        $states = [];
        $endTime = null;

        foreach ($assignments as $assignment) {
            if ($this->assignmentIsData($assignment) === false) {
                continue;
            }

            $dataAllowance = $this->dataAllowance($assignment);
            $initialBytes = $dataAllowance
                ? $this->numericValue($dataAllowance['initialAmount'] ?? null)
                : $this->numericValue($assignment['initialQuantity'] ?? null);
            $remainingBytes = $dataAllowance
                ? $this->numericValue($dataAllowance['remainingAmount'] ?? null)
                : $this->numericValue($assignment['remainingQuantity'] ?? null);

            $unlimited = $unlimited
                || (bool) ($assignment['unlimited'] ?? false)
                || (bool) ($dataAllowance['unlimited'] ?? false);

            if ($initialBytes !== null || $remainingBytes !== null) {
                $totalMb += $this->bytesToMegabytes((float) ($initialBytes ?? 0));
                $remainingMb += $this->bytesToMegabytes((float) ($remainingBytes ?? 0));
                $hasMeasuredData = true;
            }

            $state = $assignment['bundleState'] ?? $assignment['status'] ?? null;

            if ($state) {
                $states[] = (string) $state;
            }

            $candidateEnd = $assignment['endTime'] ?? $assignment['expiryDate'] ?? $assignment['expiresAt'] ?? null;

            if ($this->isLaterDate($candidateEnd, $endTime)) {
                $endTime = (string) $candidateEnd;
            }
        }

        if (! $hasMeasuredData && ! $unlimited) {
            return null;
        }

        $status = $this->bestAssignmentState($states);
        $usedMb = $hasMeasuredData ? max(0, $totalMb - $remainingMb) : null;

        return [
            'total_mb' => $hasMeasuredData ? $totalMb : null,
            'remaining_mb' => $hasMeasuredData ? $remainingMb : null,
            'used_mb' => $usedMb,
            'unlimited' => $unlimited,
            'status' => $status,
            'end_time' => $endTime,
        ];
    }

    private function assignmentIsData(array $assignment): ?bool
    {
        $callTypeGroup = strtolower((string) ($assignment['callTypeGroup'] ?? ''));

        if ($callTypeGroup !== '') {
            return $callTypeGroup === 'data';
        }

        return null;
    }

    private function dataAllowance(array $assignment): ?array
    {
        $allowances = Arr::wrap($assignment['allowances'] ?? []);

        foreach ($allowances as $allowance) {
            if (! is_array($allowance)) {
                continue;
            }

            if (strtoupper((string) ($allowance['type'] ?? '')) === 'DATA') {
                return $allowance;
            }
        }

        return null;
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function bytesToMegabytes(float $bytes): float
    {
        return $bytes / 1000 / 1000;
    }

    private function bestAssignmentState(array $states): ?string
    {
        if ($states === []) {
            return null;
        }

        $priority = ['active', 'queued', 'processing', 'depleted', 'expired', 'lapsed', 'revoked'];

        foreach ($priority as $candidate) {
            foreach ($states as $state) {
                if (strtolower($state) === $candidate) {
                    return ucfirst($candidate);
                }
            }
        }

        return $states[0];
    }

    private function isLaterDate(mixed $candidate, ?string $current): bool
    {
        if (! is_string($candidate) || trim($candidate) === '') {
            return false;
        }

        if ($current === null) {
            return true;
        }

        try {
            return Carbon::parse($candidate)->greaterThan(Carbon::parse($current));
        } catch (\Throwable) {
            return false;
        }
    }

    private function storeProviderSnapshot(CustomerEsim $esim, array $data): void
    {
        try {
            $summary = $this->summary($data, null);
            $expiresAt = $this->firstValue($data, [
                'bundle_status.assignments.0.endTime',
                'bundle_status.endTime',
                'esim.expiryDate',
                'esim.expiresAt',
            ]);

            $updates = [
                'last_status' => $data,
                'last_synced_at' => now(),
            ];

            if ($summary['usage_status'] && $summary['usage_status'] !== 'Unknown') {
                $updates['status'] = strtolower((string) $summary['usage_status']);
            }

            if (is_string($expiresAt) && trim($expiresAt) !== '') {
                $updates['expires_at'] = Carbon::parse($expiresAt);
            }

            $esim->forceFill($updates)->save();
        } catch (\Throwable) {
            $esim->forceFill([
                'last_status' => $data,
                'last_synced_at' => now(),
            ])->save();
        }
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
            'TB' => $amount * 1000 * 1000,
            'GB' => $amount * 1000,
            'KB' => $amount / 1000,
            default => $amount,
        };
    }

    private function formatMegabytes(?float $megabytes): ?string
    {
        if ($megabytes === null) {
            return null;
        }

        if ($megabytes >= 1000 * 1000) {
            return round($megabytes / 1000 / 1000, 2) . ' TB';
        }

        if ($megabytes >= 1000) {
            return round($megabytes / 1000, 2) . ' GB';
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
