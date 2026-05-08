<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request, EsimPlan $plan): Response|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        return Inertia::render('Storefront/Checkout', [
            'plan' => $plan,
        ]);
    }

    public function success(Request $request, EsimPlan $plan): Response|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()
                ->route('plans.show', $plan)
                ->with('status', 'Checkout is disabled in admin storefront preview.');
        }

        return Inertia::render('Storefront/CheckoutSuccess', [
            'plan' => $plan,
        ]);
    }
}
