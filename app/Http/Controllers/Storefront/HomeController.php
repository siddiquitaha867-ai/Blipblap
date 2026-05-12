<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Storefront/Home', [
            'featuredDestinations' => config('blipblap.featured_destinations'),
            'featuredPlans' => $this->featuredPlans(),
        ]);
    }

    private function featuredPlans()
    {
        return EsimPlan::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('duration_days')
            ->orderBy('retail_price')
            ->limit(6)
            ->get([
                'id',
                'slug',
                'title',
                'country_name',
                'region_name',
                'data_amount',
                'data_unit',
                'duration_days',
                'unlimited',
                'retail_price',
                'currency',
            ]);
    }
}
