<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    public function show(string $slug): Response
    {
        $normalizedSlug = Str::of($slug)->replace('-esim', '')->toString();
        $name = $this->nameForSlug($normalizedSlug);
        $iso = $this->isoForSlug($normalizedSlug);

        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedSlug, $name, $iso) {
                $query
                    ->where('country_name', $name)
                    ->orWhere('region_name', $name)
                    ->orWhere('country_name', 'like', '%' . str_replace('-', ' ', $normalizedSlug) . '%')
                    ->when($iso !== null, fn ($inner) => $inner->orWhere('country_iso', $iso));
            })
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();

        abort_if($plans->isEmpty(), 404);

        return Inertia::render('Storefront/PlanShow', [
            'plan' => $plans->first(),
            'relatedPlans' => $plans,
        ]);
    }

    private function nameForSlug(string $slug): string
    {
        return [
            'usa' => 'United States',
            'global' => 'Global',
            'global-1gb' => 'Global',
            'global-3gb' => 'Global',
            'global-5gb' => 'Global',
            'global-10gb' => 'Global',
            'global-20gb' => 'Global',
            'global-unlimited' => 'Global',
            'middle-east' => 'Middle East',
            'north-america' => 'North America',
            'latin-america' => 'LATAM',
        ][$slug] ?? Str::of($slug)->replace('-', ' ')->title()->toString();
    }

    private function isoForSlug(string $slug): ?string
    {
        return [
            'pakistan' => 'PK',
            'united-arab-emirates' => 'AE',
            'uae' => 'AE',
            'saudi-arabia' => 'SA',
            'russia' => 'RU',
            'oman' => 'OM',
            'egypt' => 'EG',
            'united-kingdom' => 'GB',
            'turkey' => 'TR',
            'usa' => 'US',
            'canada' => 'CA',
        ][$slug] ?? null;
    }
}
