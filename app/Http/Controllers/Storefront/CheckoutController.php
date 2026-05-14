<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Services\EsimGo\OrderProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function show(Request $request, EsimPlan $plan): Response|RedirectResponse
    {
        if (! $request->user()) {
            return $this->redirectGuestToAuth($request, route('checkout.show', $plan));
        }

        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        return Inertia::render('Storefront/Checkout', [
            'plan' => $plan,
        ]);
    }

    public function payment(Request $request, EsimPlan $plan): Response|RedirectResponse
    {
        if (! $request->user()) {
            return $this->redirectGuestToAuth($request, route('checkout.payment', $plan));
        }

        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        return Inertia::render('Storefront/Payment', [
            'plan' => $plan,
            'customerName' => (string) $request->query('name', ''),
            'customerEmail' => (string) $request->query('email', $request->user()?->email ?? ''),
            'stripePublishableKey' => config('services.stripe.key'),
        ]);
    }

    public function stripe(Request $request, EsimPlan $plan): RedirectResponse|HttpResponse
    {
        if (! $request->user()) {
            return $this->redirectGuestToAuth($request, route('checkout.show', $plan));
        }

        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
        ]);

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return back()->with('status', 'Stripe secret key is missing. Add STRIPE_SECRET to .env.');
        }

        $order = EsimOrder::query()->create([
            'user_id' => $request->user()?->id,
            'customer_email' => $data['customer_email'],
            'order_reference' => 'BB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'order_type' => 'new_esim',
            'bundle_code' => $plan->supplier_code,
            'status' => 'pending_payment',
            'validation_status' => 'pending',
            'fulfillment_status' => 'pending_payment',
            'subtotal' => $plan->retail_price,
            'total' => $plan->retail_price,
            'currency' => $plan->currency,
            'request_payload' => [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
                'customer_name' => $data['customer_name'] ?? null,
            ],
        ]);

        $amount = (int) round((float) $plan->retail_price * 100);
        $currency = strtolower($plan->currency ?: 'usd');

        $response = Http::asForm()
            ->withToken($secret)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => (string) $order->id,
                'customer_email' => $data['customer_email'],
                'success_url' => route('checkout.success', $plan) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.payment', $plan) . '?email=' . urlencode($data['customer_email']),
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'plan_id' => (string) $plan->id,
                ],
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => $currency,
                            'unit_amount' => $amount,
                            'product_data' => [
                                'name' => $plan->title,
                                'description' => ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type) . ' eSIM',
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $order->update([
                'status' => 'payment_session_failed',
                'response_payload' => $response->json(),
            ]);

            return back()->with('status', 'Stripe checkout session failed. Please check your Stripe keys.');
        }

        $session = $response->json();

        $order->update([
            'payment_reference' => $session['id'] ?? null,
            'response_payload' => $session,
        ]);

        return Inertia::location($session['url']);
    }

    public function success(Request $request, EsimPlan $plan, OrderProvisioningService $provisioning): Response|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        $sessionId = (string) $request->query('session_id', '');
        $order = $sessionId !== ''
            ? EsimOrder::query()->where('payment_reference', $sessionId)->first()
            : null;

        $provisioningError = null;

        if ($order) {
            $this->markStripeSessionPaid($order, $sessionId);

            try {
                $provisioning->provision($order->refresh(), $plan);
            } catch (\Throwable $exception) {
                $provisioningError = 'Provisioning failed. Please check the eSIM Go API key, balance, and bundle code.';

                $order->update([
                    'status' => 'provisioning_failed',
                    'fulfillment_status' => 'provisioning_failed',
                    'response_payload' => array_merge($order->response_payload ?? [], [
                        'provisioning_error' => $exception->getMessage(),
                    ]),
                ]);
            }
        }

        $customerEsim = $order
            ? CustomerEsim::query()->where('source_order_id', $order->id)->first()
            : null;

        return Inertia::render('Storefront/CheckoutSuccess', [
            'plan' => $plan,
            'order' => $order?->refresh(),
            'esim' => $customerEsim,
            'provisioningError' => $provisioningError,
        ]);
    }

    public function webhook(Request $request, OrderProvisioningService $provisioning): HttpResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $webhookSecret = (string) config('services.stripe.webhook_secret');

        if ($webhookSecret !== '' && ! $this->isValidStripeSignature($payload, $signature, $webhookSecret)) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event)) {
            return response('Invalid payload', 400);
        }

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $orderId = $session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null;

            if ($orderId) {
                $order = EsimOrder::query()->whereKey($orderId)->first();

                if ($order) {
                    $order->update([
                        'payment_reference' => $session['id'] ?? null,
                        'apply_reference' => $session['payment_intent'] ?? null,
                        'status' => 'paid',
                        'validation_status' => 'paid',
                        'fulfillment_status' => 'ready_for_provisioning',
                        'paid_at' => now(),
                        'response_payload' => $session,
                    ]);

                    try {
                        $plan = EsimPlan::query()->where('supplier_code', $order->bundle_code)->first();
                        $provisioning->provision($order->refresh(), $plan);
                    } catch (\Throwable $exception) {
                        $order->update([
                            'status' => 'provisioning_failed',
                            'fulfillment_status' => 'provisioning_failed',
                            'response_payload' => array_merge($order->response_payload ?? [], [
                                'provisioning_error' => $exception->getMessage(),
                            ]),
                        ]);
                    }
                }
            }
        }

        return response('Webhook received');
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
            'apply_reference' => $session['payment_intent'] ?? $order->apply_reference,
            'status' => 'paid',
            'validation_status' => 'paid',
            'fulfillment_status' => 'ready_for_provisioning',
            'paid_at' => $order->paid_at ?: now(),
            'response_payload' => $session,
        ]);
    }

    private function redirectGuestToAuth(Request $request, string $intendedUrl): RedirectResponse
    {
        $request->session()->put('url.intended', $intendedUrl);

        return redirect()
            ->route('auth.login', ['redirect' => $intendedUrl])
            ->with('status', 'Please log in or create an account before checkout.');
    }

    private function isValidStripeSignature(string $payload, string $signature, string $secret): bool
    {
        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $expectedSignature = $parts->get('v1');

        if (! $timestamp || ! $expectedSignature) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($computedSignature, $expectedSignature);
    }
}
