<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Services\EsimGo\EsimGoClient;
use App\Services\EsimGo\OrderProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TopupController extends Controller
{
    public function show(Request $request, CustomerEsim $esim): Response|RedirectResponse
    {
        $this->authorizeOwnedEsim($request, $esim);

        if (! $this->isTopupEligible($esim)) {
            return redirect()
                ->route('my-esims')
                ->with('status', 'This eSIM is not available for top-up.');
        }

        return Inertia::render('Storefront/Topup', [
            'esim' => $this->esimPayload($esim),
            'sourcePlan' => $this->planPayload($this->sourcePlan($esim)),
            'packages' => $this->topupPackages($esim)->map(fn (EsimPlan $plan): array => $this->planPayload($plan))->values(),
            'csrfToken' => csrf_token(),
        ]);
    }

    public function stripe(Request $request, CustomerEsim $esim, EsimGoClient $client): RedirectResponse|HttpResponse
    {
        $this->authorizeOwnedEsim($request, $esim);

        if (! $this->isTopupEligible($esim)) {
            return back()->with('status', 'This eSIM is not available for top-up.');
        }

        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'terms_accepted' => ['accepted'],
        ]);

        $plan = $this->topupPackages($esim)
            ->firstWhere('id', (int) $data['plan_id']);

        if (! $plan) {
            return back()->with('status', 'The selected top-up package is not compatible with this eSIM.');
        }

        try {
            $client->compatibility((string) $esim->iccid, (string) $plan->supplier_code);
        } catch (\Throwable) {
            return back()->with('status', 'This top-up package is not compatible with your eSIM. Please choose another package.');
        }

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return back()->with('status', 'Stripe secret key is missing. Add STRIPE_SECRET to .env.');
        }

        $subtotal = (float) $plan->retail_price;
        $taxAmount = (float) ($plan->tax_amount ?? 0);
        $total = round($subtotal + $taxAmount, 2);

        $order = EsimOrder::query()->create([
            'user_id' => $request->user()?->id,
            'customer_email' => $request->user()?->email,
            'order_reference' => 'BB-TOPUP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'order_type' => 'topup',
            'bundle_code' => $plan->supplier_code,
            'iccid' => $esim->iccid,
            'status' => 'pending_payment',
            'validation_status' => 'pending',
            'fulfillment_status' => 'pending_payment',
            'subtotal' => $subtotal,
            'total' => $total,
            'currency' => $plan->currency,
            'request_payload' => [
                'source_esim_id' => $esim->id,
                'source_order_id' => $esim->source_order_id,
                'source_plan_id' => $this->sourcePlan($esim)?->id,
                'topup_plan_id' => $plan->id,
                'tax_amount' => $taxAmount,
                'terms_accepted' => true,
                'terms_accepted_at' => now()->toIso8601String(),
            ],
        ]);

        $response = Http::asForm()
            ->withToken($secret)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => (string) $order->id,
                'customer_email' => $request->user()?->email,
                'success_url' => route('topup.success', $esim) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('topup.show', $esim),
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_reference' => (string) $order->order_reference,
                    'order_type' => 'topup',
                    'customer_esim_id' => (string) $esim->id,
                    'iccid' => (string) $esim->iccid,
                    'plan_id' => (string) $plan->id,
                ],
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => strtolower($plan->currency ?: 'usd'),
                            'unit_amount' => (int) round($total * 100),
                            'product_data' => [
                                'name' => 'Top-up: ' . $plan->title,
                                'description' => 'Top-up for ICCID ' . $esim->iccid,
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $stripeError = (string) data_get($response->json(), 'error.message', 'Please check your Stripe keys and live payment settings.');

            $order->update([
                'status' => 'payment_session_failed',
                'response_payload' => $response->json(),
            ]);

            return back()->with('status', 'Stripe checkout session failed: ' . $stripeError);
        }

        $session = $response->json();

        $order->update([
            'payment_reference' => $session['id'] ?? null,
            'response_payload' => $session,
        ]);

        $checkoutUrl = (string) ($session['url'] ?? '');

        if ($checkoutUrl === '') {
            $order->update([
                'status' => 'payment_session_failed',
                'response_payload' => $session,
            ]);

            return back()->with('status', 'Stripe checkout session did not return a payment URL.');
        }

        return redirect()->away($checkoutUrl);
    }

    public function success(Request $request, CustomerEsim $esim, OrderProvisioningService $provisioning): Response|RedirectResponse
    {
        $this->authorizeOwnedEsim($request, $esim);

        $sessionId = (string) $request->query('session_id', '');
        $order = $sessionId !== ''
            ? EsimOrder::query()
                ->where('payment_reference', $sessionId)
                ->where('order_type', 'topup')
                ->where('iccid', $esim->iccid)
                ->first()
            : null;

        $topupError = null;
        $plan = null;

        if (! $order) {
            $topupError = 'We could not confirm this top-up payment session. Please check My eSIMs or contact support if payment was taken.';
        } else {
            $this->markStripeSessionPaid($order, $sessionId);
            $plan = EsimPlan::query()->where('supplier_code', $order->bundle_code)->first();

            try {
                $provisioning->applyTopup($order->refresh(), $plan);
            } catch (\Throwable $exception) {
                $topupError = 'Top-up failed. Please check the eSIM Go API key, bundle compatibility, and provider balance.';

                $order->update([
                    'status' => 'topup_failed',
                    'fulfillment_status' => 'topup_failed',
                    'response_payload' => array_merge($order->response_payload ?? [], [
                        'topup_error' => $exception->getMessage(),
                    ]),
                ]);
            }
        }

        return Inertia::render('Storefront/TopupSuccess', [
            'esim' => $this->esimPayload($esim->refresh()),
            'plan' => $this->planPayload($plan),
            'order' => $order?->refresh(),
            'topupError' => $topupError,
        ]);
    }

    private function authorizeOwnedEsim(Request $request, CustomerEsim $esim): void
    {
        abort_unless(
            $esim->user_id === $request->user()?->id || $esim->customer_email === $request->user()?->email,
            404,
        );
    }

    private function isTopupEligible(CustomerEsim $esim): bool
    {
        return filled($esim->iccid) && $this->topupPackages($esim)->isNotEmpty();
    }

    private function sourcePlan(CustomerEsim $esim): ?EsimPlan
    {
        $order = $esim->order;
        $planId = $order?->request_payload['plan_id'] ?? null;

        if ($planId) {
            return EsimPlan::query()->find($planId);
        }

        if ($esim->current_bundle_code) {
            return EsimPlan::query()->where('supplier_code', $esim->current_bundle_code)->first();
        }

        return null;
    }

    private function topupPackages(CustomerEsim $esim)
    {
        $sourcePlan = $this->sourcePlan($esim);

        $query = EsimPlan::query()
            ->where('is_active', true)
            ->where('topup_supported', true);

        if ($sourcePlan?->country_name) {
            $query->where('country_name', $sourcePlan->country_name);
        } elseif ($sourcePlan?->region_name) {
            $query->where('region_name', $sourcePlan->region_name);
        } elseif ($sourcePlan?->coverage_type) {
            $query->where('coverage_type', $sourcePlan->coverage_type);
        } else {
            $query->where('supplier_code', $esim->current_bundle_code ?: '');
        }

        return $query
            ->when(
                (clone $query)->where('topup_supported', true)->exists(),
                fn ($builder) => $builder->where('topup_supported', true),
            )
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();
    }

    private function esimPayload(CustomerEsim $esim): array
    {
        return [
            'id' => $esim->id,
            'iccid' => $esim->iccid,
            'nickname' => $esim->nickname,
            'status' => $esim->status,
            'current_bundle_code' => $esim->current_bundle_code,
            'topup_supported' => (bool) $esim->topup_supported,
        ];
    }

    private function planPayload(?EsimPlan $plan): ?array
    {
        if (! $plan) {
            return null;
        }

        $taxAmount = (float) ($plan->tax_amount ?? 0);
        $retailPrice = (float) $plan->retail_price;

        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'supplier_code' => $plan->supplier_code,
            'country_name' => $plan->country_name,
            'region_name' => $plan->region_name,
            'coverage_type' => $plan->coverage_type,
            'data_amount' => $plan->data_amount,
            'data_unit' => $plan->data_unit,
            'duration_days' => $plan->duration_days,
            'unlimited' => (bool) $plan->unlimited,
            'retail_price' => $retailPrice,
            'tax_amount' => $taxAmount,
            'total_price' => round($retailPrice + $taxAmount, 2),
            'currency' => $plan->currency,
        ];
    }

    private function markStripeSessionPaid(EsimOrder $order, string $sessionId): void
    {
        if ($order->status !== 'pending_payment' || $sessionId === '') {
            return;
        }

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return;
        }

        $response = Http::withToken($secret)
            ->get('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId));

        if (! $response->successful()) {
            return;
        }

        $session = $response->json() ?? [];

        if (($session['payment_status'] ?? '') !== 'paid' && ($session['status'] ?? '') !== 'complete') {
            return;
        }

        $order->update([
            'status' => 'paid',
            'validation_status' => 'paid',
            'fulfillment_status' => 'ready_for_topup',
            'paid_at' => $order->paid_at ?: now(),
            'response_payload' => $session,
        ]);
    }
}
