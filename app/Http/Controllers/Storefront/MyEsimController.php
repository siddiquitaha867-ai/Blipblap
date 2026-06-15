<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use App\Services\EsimUsageService;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyEsimController extends Controller
{
    public function __invoke(Request $request, EsimGoClient $client, EsimUsageService $usageService): Response
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
            'esims' => $esims->map(function (CustomerEsim $esim) use ($plans, $client, $usageService): array {
                $order = $esim->order;
                $planId = $order?->request_payload['plan_id'] ?? null;
                $plan = $planId ? $plans->get($planId) : null;
                $providerData = $usageService->providerEsimData($client, $esim);
                $usage = $usageService->summary($providerData, $plan);
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
                    'used_data' => $usage['used_data'],
                    'remaining_data' => $usage['remaining_data'],
                    'total_data' => $usage['total_data'],
                    'usage_percent' => $usage['usage_percent'],
                    'remaining_percent' => $usage['remaining_percent'],
                    'usage_status' => $usage['usage_status'],
                    'ios_install_url' => $install['ios_install_url'],
                    'android_install_url' => $install['android_install_url'],
                    'can_topup' => filled($esim->iccid) && $this->hasTopupPackages($esim, $plan),
                    'topup_url' => route('topup.show', $esim),
                ];
            })->values(),
        ]);
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
            'ios_install_url' => $ios ?: $this->fallbackInstallUrl('apple', $esim),
            'android_install_url' => $android ?: $this->fallbackInstallUrl('android', $esim),
        ];
    }

    private function fallbackInstallUrl(string $platform, CustomerEsim $esim): ?string
    {
        $activationCode = trim((string) $esim->activation_code);

        if ($activationCode === '' && $esim->smdp_address && $esim->matching_id) {
            $activationCode = 'LPA:1$' . $esim->smdp_address . '$' . $esim->matching_id;
        }

        if ($activationCode === '') {
            return null;
        }

        $host = $platform === 'android'
            ? 'https://esimsetup.android.com/esim_qrcode_provisioning'
            : 'https://esimsetup.apple.com/esim_qrcode_provisioning';

        return $host . '?carddata=' . rawurlencode($activationCode);
    }

    private function hasTopupPackages(CustomerEsim $esim, ?EsimPlan $plan): bool
    {
        if (! $plan && $esim->current_bundle_code) {
            $plan = EsimPlan::query()->where('supplier_code', $esim->current_bundle_code)->first();
        }

        if (! $plan) {
            return false;
        }

        $query = EsimPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($plan): void {
                if ($plan->country_name) {
                    $query->where('country_name', $plan->country_name);
                } elseif ($plan->region_name) {
                    $query->where('region_name', $plan->region_name);
                } else {
                    $query->where('coverage_type', $plan->coverage_type);
                }
            });

        if ((clone $query)->where('topup_supported', true)->exists()) {
            $query->where('topup_supported', true);
        }

        return $query->exists();
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

}
