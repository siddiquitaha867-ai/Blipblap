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

        if (! $request->boolean('terms_accepted')) {
            return redirect()
                ->route('checkout.show', $plan)
                ->with('status', 'Please agree to the Terms and Conditions and Privacy Policy before payment.');
        }

        $billing = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
        ]);

        return Inertia::render('Storefront/Payment', [
            'plan' => $plan,
            'customerName' => (string) $billing['name'],
            'customerEmail' => (string) $billing['email'],
            'customerPhone' => (string) $billing['phone'],
            'addressLine1' => (string) $billing['address_line1'],
            'addressLine2' => (string) ($billing['address_line2'] ?? ''),
            'city' => (string) $billing['city'],
            'state' => (string) $billing['state'],
            'postalCode' => (string) $billing['postal_code'],
            'country' => (string) $billing['country'],
            'csrfToken' => csrf_token(),
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'terms_accepted' => ['accepted'],
        ]);

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return back()->with('status', 'Stripe secret key is missing. Add STRIPE_SECRET to .env.');
        }

        $subtotal = (float) $plan->retail_price;
        $taxAmount = (float) ($plan->tax_amount ?? 0);
        $total = round($subtotal + $taxAmount, 2);

        $order = EsimOrder::query()->create([
            'user_id' => $request->user()?->id,
            'customer_email' => $data['customer_email'],
            'order_reference' => 'BB-ESIM-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'order_type' => 'new_esim',
            'bundle_code' => $plan->supplier_code,
            'status' => 'pending_payment',
            'validation_status' => 'pending',
            'fulfillment_status' => 'pending_payment',
            'subtotal' => $subtotal,
            'total' => $total,
            'currency' => $plan->currency,
            'request_payload' => [
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
                'tax_amount' => $taxAmount,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? null,
                'terms_accepted' => true,
                'terms_accepted_at' => now()->toIso8601String(),
            ],
        ]);

        $amount = (int) round($total * 100);
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
                    'order_reference' => (string) $order->order_reference,
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
                        'status' => 'paid',
                        'validation_status' => 'paid',
                        'fulfillment_status' => 'ready_for_provisioning',
                        'paid_at' => now(),
                        'response_payload' => $session,
                    ]);

                    try {
                        $plan = EsimPlan::query()->where('supplier_code', $order->bundle_code)->first();

                        if ($order->order_type === 'topup') {
                            $provisioning->applyTopup($order->refresh(), $plan);
                        } else {
                            $provisioning->provision($order->refresh(), $plan);
                        }
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
