<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(EsimPlan $plan): Response
    {
        return Inertia::render('Storefront/Checkout', [
            'plan' => $plan,
        ]);
    }

    public function success(EsimPlan $plan): Response
    {
        return Inertia::render('Storefront/CheckoutSuccess', [
            'plan' => $plan,
        ]);
    }
}
