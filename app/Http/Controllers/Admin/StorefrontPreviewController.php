<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontPreviewController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Storefront/Home', [
            'featuredDestinations' => config('blipblap.featured_destinations'),
            'featuredPlans' => EsimPlan::query()
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
                ]),
            'storefrontPreview' => true,
        ]);
    }
}
