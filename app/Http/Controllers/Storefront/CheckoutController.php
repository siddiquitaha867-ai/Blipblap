<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Models\PromotionRule;
use App\Services\EsimGo\OrderProvisioningService;
use App\Services\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'promotion_code' => ['nullable', 'string', 'max:40'],
        ]);

        $promotion = $this->validatedPromotion((string) ($billing['promotion_code'] ?? ''), $plan);
        $pricing = $this->pricing($plan, $promotion);

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
            'promotionCode' => $promotion['code'] ?? '',
            'promotionDiscount' => $promotion,
            'pricing' => $pricing,
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
            'promotion_code' => ['nullable', 'string', 'max:40'],
            'terms_accepted' => ['accepted'],
        ]);

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return back()->with('status', 'Stripe secret key is missing. Add STRIPE_SECRET to .env.');
        }

        $promotion = $this->validatedPromotion((string) ($data['promotion_code'] ?? ''), $plan);
        $pricing = $this->pricing($plan, $promotion);
        $subtotal = $pricing['subtotal'];
        $taxAmount = $pricing['tax_amount'];
        $discountAmount = $pricing['discount_amount'];
        $total = $pricing['total'];

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
                'discount_amount' => $discountAmount,
                'promotion' => $promotion,
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
                    'promotion_code' => (string) ($promotion['code'] ?? ''),
                    'discount_amount' => (string) $discountAmount,
                ],
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => $currency,
                            'unit_amount' => $amount,
                            'product_data' => [
                                'name' => $promotion ? $plan->title . ' (' . $promotion['code'] . ' applied)' : $plan->title,
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

    public function success(Request $request, EsimPlan $plan, OrderProvisioningService $provisioning, LoyaltyService $loyalty): Response|RedirectResponse
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
            $loyalty->awardPurchasePoints($order->refresh());

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
            'loyalty' => $request->user() ? $loyalty->summaryForUser($request->user()) : null,
        ]);
    }

    public function webhook(Request $request, OrderProvisioningService $provisioning, LoyaltyService $loyalty): HttpResponse
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

                    $loyalty->awardPurchasePoints($order->refresh());

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

    private function validatedPromotion(string $code, EsimPlan $plan): ?array
    {
        $normalizedCode = $this->normalizePromotionCode($code);

        if ($normalizedCode === '') {
            return null;
        }

        $promotion = PromotionRule::query()
            ->where('is_active', true)
            ->where('conditions->code', $normalizedCode)
            ->first();

        if (! $promotion) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code is not valid.']);
        }

        if ($promotion->starts_at && $promotion->starts_at->isFuture()) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code is not active yet.']);
        }

        if ($promotion->ends_at && $promotion->ends_at->isPast()) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code has expired.']);
        }

        $conditions = $promotion->conditions ?? [];
        $actions = $promotion->actions ?? [];
        $appliesTo = (string) ($conditions['applies_to'] ?? 'all');
        $planIds = collect($conditions['plan_ids'] ?? [])->map(fn ($id): int => (int) $id)->all();

        if ($appliesTo === 'plans' && ! in_array((int) $plan->id, $planIds, true)) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code is not available for the selected plan.']);
        }

        $usageLimit = $conditions['usage_limit'] ?? null;

        if ($usageLimit && $this->promotionUsageCount($normalizedCode) >= (int) $usageLimit) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code has reached its usage limit.']);
        }

        $discountType = (string) ($actions['discount_type'] ?? '');
        $discountValue = (float) ($actions['discount_value'] ?? 0);

        if (! in_array($discountType, ['percent', 'fixed'], true) || $discountValue <= 0) {
            throw ValidationException::withMessages(['promotion_code' => 'This promotion code is not configured correctly.']);
        }

        return [
            'id' => $promotion->id,
            'title' => $promotion->title,
            'code' => $normalizedCode,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'applies_to' => $appliesTo,
        ];
    }

    private function pricing(EsimPlan $plan, ?array $promotion): array
    {
        $subtotal = round((float) $plan->retail_price, 2);
        $taxAmount = round((float) ($plan->tax_amount ?? 0), 2);
        $discountAmount = 0.0;

        if ($promotion) {
            $discountAmount = $promotion['discount_type'] === 'fixed'
                ? (float) $promotion['discount_value']
                : $subtotal * ((float) $promotion['discount_value'] / 100);
            $discountAmount = round(min($subtotal, max(0, $discountAmount)), 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => round(max(0, $subtotal - $discountAmount) + $taxAmount, 2),
        ];
    }

    private function normalizePromotionCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Z0-9_-]/i', '', trim($code)) ?? '');
    }

    private function promotionUsageCount(string $code): int
    {
        return EsimOrder::query()
            ->whereIn('status', ['paid', 'provisioning', 'provisioned'])
            ->where('request_payload->promotion->code', $code)
            ->count();
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
