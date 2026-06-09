<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use App\Support\StorefrontPlanPresenter;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function show(EsimPlan $plan): Response
    {
        $related = EsimPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($plan): void {
                $query->where('country_iso', $plan->country_iso)
                    ->orWhere('country_name', $plan->country_name);
            })
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();

        return Inertia::render('Storefront/PlanShow', [
            'plan' => StorefrontPlanPresenter::present($plan),
            'relatedPlans' => StorefrontPlanPresenter::collection($related),
        ]);
    }
}
