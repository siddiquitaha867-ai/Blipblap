<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use App\Services\EsimUsageService;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                return $this->esimPayload($esim, $plans, $client, $usageService);
            })->values(),
        ]);
    }

    public function usage(Request $request, CustomerEsim $esim, EsimGoClient $client, EsimUsageService $usageService): JsonResponse
    {
        $this->authorizeOwnedEsim($request, $esim);

        $plan = $this->planForEsim($esim->loadMissing('order'), collect());
        $providerData = $usageService->providerEsimData($client, $esim);
        $usage = $usageService->summary($providerData, $plan);

        return response()->json([
            'status' => $usage['usage_status'],
            'days_remaining' => $usage['days_remaining'],
            'used_data' => $usage['used_data'],
            'remaining_data' => $usage['remaining_data'],
            'total_data' => $usage['total_data'],
            'usage_percent' => $usage['usage_percent'],
            'remaining_percent' => $usage['remaining_percent'],
            'usage_status' => $usage['usage_status'],
            'expires_at' => $esim->refresh()->expires_at?->toFormattedDateString(),
            'last_synced_at' => $esim->last_synced_at?->toDateTimeString(),
        ]);
    }

    private function esimPayload(CustomerEsim $esim, Collection $plans, EsimGoClient $client, EsimUsageService $usageService): array
    {
        $order = $esim->order;
        $plan = $this->planForEsim($esim, $plans);
        $providerData = $usageService->providerEsimData($client, $esim);
        $usage = $usageService->summary($providerData, $plan);
        $install = $this->installLinks($providerData, $esim);
        $syncedEsim = $esim->refresh();

        return [
            'id' => $syncedEsim->id,
            'plan_title' => $plan?->title ?: $syncedEsim->nickname ?: $order?->bundle_code ?: 'BlipBlap eSIM',
            'location' => $plan?->country_name ?: $plan?->region_name ?: 'Global',
            'data' => $plan?->unlimited ? 'Unlimited' : trim((string) ($plan?->data_amount . ' ' . $plan?->data_unit)),
            'duration_days' => $plan?->duration_days,
            'iccid' => $syncedEsim->iccid,
            'status' => $syncedEsim->status,
            'qr_code_url' => $syncedEsim->qr_code_url,
            'smdp_address' => $syncedEsim->smdp_address,
            'matching_id' => $syncedEsim->matching_id,
            'activation_code' => $syncedEsim->activation_code,
            'order_reference' => $order?->order_reference,
            'created_at' => $syncedEsim->created_at?->toFormattedDateString(),
            'expires_at' => $syncedEsim->expires_at?->toFormattedDateString(),
            'last_synced_at' => $syncedEsim->last_synced_at?->toDateTimeString(),
            'days_remaining' => $usage['days_remaining'],
            'used_data' => $usage['used_data'],
            'remaining_data' => $usage['remaining_data'],
            'total_data' => $usage['total_data'],
            'usage_percent' => $usage['usage_percent'],
            'remaining_percent' => $usage['remaining_percent'],
            'usage_status' => $usage['usage_status'],
            'ios_install_url' => $install['ios_install_url'],
            'android_install_url' => $install['android_install_url'],
            'can_topup' => filled($syncedEsim->iccid) && $this->hasTopupPackages($syncedEsim, $plan),
            'topup_url' => route('topup.show', $syncedEsim),
        ];
    }

    private function planForEsim(CustomerEsim $esim, Collection $plans): ?EsimPlan
    {
        $planId = $esim->order?->request_payload['plan_id'] ?? null;
        $plan = $planId ? $plans->get($planId) : null;

        if (! $plan && $planId) {
            $plan = EsimPlan::query()->find($planId);
        }

        if (! $plan && $esim->current_bundle_code) {
            $plan = EsimPlan::query()->where('supplier_code', $esim->current_bundle_code)->first();
        }

        return $plan;
    }

    private function authorizeOwnedEsim(Request $request, CustomerEsim $esim): void
    {
        abort_unless(
            $request->user()
            && ($esim->user_id === $request->user()->id || $esim->customer_email === $request->user()->email),
            403
        );
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
