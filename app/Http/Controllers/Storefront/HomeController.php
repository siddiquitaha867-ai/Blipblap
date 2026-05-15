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
            'featuredDestinations' => $this->featuredDestinations(),
            'featuredPlans' => $this->featuredPlans(),
        ]);
    }

    private function featuredDestinations()
    {
        $fallback = collect(config('blipblap.featured_destinations', []));

        $destinations = EsimPlan::query()
            ->where('is_active', true)
            ->get(['country_name', 'region_name', 'coverage_type', 'country_iso'])
            ->map(function (EsimPlan $plan): array {
                return [
                    'name' => $plan->country_name ?: $plan->region_name ?: $plan->coverage_type,
                    'iso' => $plan->country_iso,
                ];
            })
            ->filter(fn (array $destination): bool => trim((string) $destination['name']) !== '')
            ->groupBy(fn (array $destination): string => (string) $destination['name'])
            ->map(function ($plans, string $name): array {
                $iso = collect($plans)->pluck('iso')->filter()->first();

                return [
                    'name' => $name,
                    'iso' => $iso ?: strtoupper(substr($name, 0, 2)),
                    'plan_count' => count($plans),
                    'flag_url' => $this->flagUrl($iso),
                ];
            })
            ->sortByDesc('plan_count')
            ->take(12)
            ->values();

        return $destinations->isNotEmpty() ? $destinations : $fallback;
    }

    private function featuredPlans()
    {
        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('duration_days')
            ->orderBy('retail_price')
            ->limit(6)
            ->get($this->planColumns());

        if ($plans->count() >= 6) {
            return $plans;
        }

        $extraPlans = EsimPlan::query()
            ->where('is_active', true)
            ->when($plans->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $plans->pluck('id')))
            ->orderByRaw('COALESCE(country_name, region_name, coverage_type)')
            ->orderBy('duration_days')
            ->orderBy('retail_price')
            ->limit(6 - $plans->count())
            ->get($this->planColumns());

        return $plans->concat($extraPlans)->values();
    }

    private function planColumns(): array
    {
        return [
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
        ];
    }

    private function flagUrl(?string $iso): string
    {
        $localFlagMap = [
            'AE' => '/images/blipblap/ARE.svg',
            'EG' => '/images/blipblap/EGY.svg',
            'GB' => '/images/blipblap/GBR.svg',
            'OM' => '/images/blipblap/OMN.svg',
            'RU' => '/images/blipblap/RUS.svg',
            'SA' => '/images/blipblap/SAU.svg',
            'TR' => '/images/blipblap/TUR.svg',
            'US' => '/images/blipblap/USA.svg',
            'EU' => '/images/blipblap/EUR.svg',
        ];

        if (! $iso) {
            return '';
        }

        $normalizedIso = strtoupper($iso);

        if (isset($localFlagMap[$normalizedIso])) {
            return $localFlagMap[$normalizedIso];
        }

        return strlen($normalizedIso) === 2
            ? 'https://flagcdn.com/w80/' . strtolower($normalizedIso) . '.png'
            : '';
    }
}
