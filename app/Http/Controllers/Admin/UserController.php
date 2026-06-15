<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Models\User;
use App\Services\EsimGo\EsimGoClient;
use App\Services\EsimUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('referral_code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(User $user, EsimGoClient $client, EsimUsageService $usageService): Response
    {
        $orders = EsimOrder::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->latest()
            ->limit(25)
            ->get();

        $esims = CustomerEsim::query()
            ->with('order')
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->latest()
            ->limit(25)
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

        return Inertia::render('Admin/Users/Show', [
            'customer' => $user,
            'orders' => $orders->map(fn (EsimOrder $order): array => [
                'id' => $order->id,
                'bundle_code' => $order->bundle_code,
                'fulfillment_status' => $order->fulfillment_status,
                'status' => $order->status,
                'currency' => $order->currency,
                'total' => (float) $order->total,
                'paid_at' => optional($order->paid_at)?->toDateTimeString(),
                'customer_name' => (string) data_get($order->request_payload, 'customer_name', ''),
                'address' => implode(', ', array_filter([
                    data_get($order->request_payload, 'address_line1'),
                    data_get($order->request_payload, 'city'),
                    data_get($order->request_payload, 'state'),
                    data_get($order->request_payload, 'postal_code'),
                    data_get($order->request_payload, 'country'),
                ])),
            ])->values(),
            'esims' => $esims->map(function (CustomerEsim $esim) use ($plans, $client, $usageService): array {
                $planId = $esim->order?->request_payload['plan_id'] ?? null;
                $plan = $planId ? $plans->get($planId) : null;
                $usage = $usageService->summary($usageService->providerEsimData($client, $esim), $plan);

                return [
                    'id' => $esim->id,
                    'iccid' => $esim->iccid,
                    'status' => $esim->status,
                    'current_bundle_code' => $esim->current_bundle_code,
                    'customer_email' => $esim->customer_email,
                    'plan_title' => $plan?->title ?: $esim->nickname ?: $esim->order?->bundle_code ?: 'BlipBlap eSIM',
                    'used_data' => $usage['used_data'],
                    'remaining_data' => $usage['remaining_data'],
                    'total_data' => $usage['total_data'],
                    'usage_percent' => $usage['usage_percent'],
                    'remaining_percent' => $usage['remaining_percent'],
                    'usage_status' => $usage['usage_status'],
                    'days_remaining' => $usage['days_remaining'],
                ];
            })->values(),
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()?->is($user), 422, 'You cannot ban your own admin account.');

        $user->forceFill(['is_banned' => true])->save();

        return back()->with('status', 'User account banned.');
    }

    public function unban(User $user): RedirectResponse
    {
        $user->forceFill(['is_banned' => false])->save();

        return back()->with('status', 'User account unbanned.');
    }

    public function sendResetPassword(User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', 'Password reset email sent to ' . $user->email . '.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()?->is($user), 422, 'You cannot delete your own admin account.');

        CustomerEsim::query()->where('user_id', $user->id)->update(['user_id' => null]);
        EsimOrder::query()->where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User account deleted.');
    }
}
