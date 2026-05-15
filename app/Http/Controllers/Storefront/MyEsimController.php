<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyEsimController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $esims = CustomerEsim::query()
            ->with('order')
            ->where('user_id', $request->user()->id)
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
                ];
            })->values(),
        ]);
    }
}
