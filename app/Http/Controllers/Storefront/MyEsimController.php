<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MyEsimController extends Controller
{
    public function __invoke(Request $request, EsimGoClient $client): Response
    {
        $esims = CustomerEsim::query()
            ->with('order')
            ->where(function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->orWhere('customer_email', $request->user()->email);
            })
            ->latest()
            ->get();

        $planIds = $esims
            ->pluck('order.request_payload.plan_id')
            ->filter()
            ->unique()
            ->values();

        $plans = EsimPlan::query()
            ->whereIn('id', $planIds)
            ->get()
            ->keyBy('id');

        return Inertia::render('Storefront/MyEsims', [
            'esims' => $esims->map(function (CustomerEsim $esim) use ($plans): array {
                $order = $esim->order;
                $planId = $order?->request_payload['plan_id'] ?? null;
                $plan = $planId ? $plans->get($planId) : null;
                $providerData = $this->providerEsimData($client, $esim);
                $usage = $this->usageSummary($providerData, $plan);
                $install = $this->installLinks($providerData, $esim);

                return [
                    'id' => $esim->id,
                    'plan_title' => $plan?->title ?: $esim->nickname ?: $order?->bundle_code ?: 'BlipBlap eSIM',
                    'location' => $plan?->country_name ?: $plan?->region_name ?: 'Global',
                    'data' => $plan?->unlimited ? 'Unlimited' : trim((string) ($plan?->data_amount . ' ' . $plan?->data_unit)),
                    'duration_days' => $plan?->duration_days,
                    'iccid' => $esim->iccid,
                    'status' => $esim->status,
                    'qr_code_url' => $esim->qr_code_url,
                    'smdp_address' => $esim->smdp_address,
                    'matching_id' => $esim->matching_id,
                    'activation_code' => $esim->activation_code,
                    'order_reference' => $order?->order_reference,
                    'created_at' => $esim->created_at?->toFormattedDateString(),
                    'expires_at' => $esim->expires_at?->toFormattedDateString(),
                    'days_remaining' => $usage['days_remaining'],
                    'remaining_data' => $usage['remaining_data'],
                    'total_data' => $usage['total_data'],
                    'usage_status' => $usage['usage_status'],
                    'ios_install_url' => $install['ios_install_url'],
                    'android_install_url' => $install['android_install_url'],
                ];
            })->values(),
        ]);
    }

    private function providerEsimData(EsimGoClient $client, CustomerEsim $esim): array
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

    private function usageSummary(array $providerData, ?EsimPlan $plan): array
    {
        $remainingData = $this->firstValue($providerData, [
            'bundle_status.remainingData',
            'bundle_status.remaining_data',
            'bundle_status.data.remaining',
            'bundle_status.allowance.remaining',
            'esim.remainingData',
            'esim.remaining_data',
            'esim.data.remaining',
        ]);
        $totalData = $this->firstValue($providerData, [
            'bundle_status.totalData',
            'bundle_status.total_data',
            'bundle_status.data.total',
            'bundle_status.allowance.total',
            'esim.totalData',
            'esim.total_data',
        ]);
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
            'remaining_data' => $this->normalizeDataLabel($remainingData, $plan),
            'total_data' => $this->normalizeDataLabel($totalData, $plan),
            'usage_status' => $this->firstValue($providerData, [
                'bundle_status.status',
                'esim.status',
            ]) ?: 'Unknown',
        ];
    }

    private function installLinks(array $providerData, CustomerEsim $esim): array
    {
        $sources = [
            $esim->install_details ?? [],
            $providerData['bundle_status'] ?? [],
            $providerData['esim'] ?? [],
        ];

        $ios = null;
        $android = null;

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            $ios ??= $this->firstValue(['source' => $source], [
                'source.ios_install_url',
                'source.install.ios_url',
                'source.install.ios',
                'source.assignment.iosInstallUrl',
                'source.response.iosInstallUrl',
            ]);
            $android ??= $this->firstValue(['source' => $source], [
                'source.android_install_url',
                'source.install.android_url',
                'source.install.android',
                'source.assignment.androidInstallUrl',
                'source.response.androidInstallUrl',
            ]);
        }

        return [
            'ios_install_url' => $ios,
            'android_install_url' => $android,
        ];
    }

    private function firstValue(array $source, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeDataLabel(mixed $value, ?EsimPlan $plan): ?string
    {
        if ($value === null || $value === '') {
            return $plan?->unlimited ? 'Unlimited' : null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if ($numeric >= 1024) {
                return round($numeric / 1024, 2) . ' GB';
            }

            return round($numeric, 2) . ' MB';
        }

        return (string) $value;
    }

    private function daysRemaining(mixed $dateValue, ?int $fallbackDuration): ?int
    {
        if (is_string($dateValue) && trim($dateValue) !== '') {
            try {
                return max(0, Carbon::parse($dateValue)->diffInDays(now(), false) * -1);
            } catch (\Throwable) {
                return $fallbackDuration;
            }
        }

        return $fallbackDuration;
    }
}
