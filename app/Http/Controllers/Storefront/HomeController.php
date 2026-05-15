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
            'destinationGroups' => $this->destinationGroups(),
            'featuredPlans' => $this->featuredPlans(),
        ]);
    }

    private function featuredDestinations()
    {
        return $this->destinationGroups()['Top Destinations'];
    }

    private function destinationGroups(): array
    {
        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->get([
                'country_name',
                'region_name',
                'coverage_type',
                'country_iso',
                'retail_price',
                'currency',
            ]);

        $fallback = collect(config('blipblap.featured_destinations', []))
            ->take(9)
            ->values();

        if ($plans->isEmpty()) {
            return [
                'Top Destinations' => $fallback,
                'Local eSIMs' => $fallback,
                'Regional Packs' => collect()->values(),
                'Worldwide eSIMs' => collect()->values(),
            ];
        }

        $local = $this->rankedDestinations($plans->where('coverage_type', 'local'));
        $regional = $this->rankedDestinations(
            $plans->reject(fn (EsimPlan $plan): bool => $plan->coverage_type === 'local' || $this->isGlobalPlan($plan))
        );
        $worldwide = $this->rankedDestinations($plans->filter(fn (EsimPlan $plan): bool => $this->isGlobalPlan($plan)));

        return [
            'Top Destinations' => $this->rankedDestinations($plans),
            'Local eSIMs' => $local,
            'Regional Packs' => $regional,
            'Worldwide eSIMs' => $worldwide,
        ];
    }

    private function rankedDestinations($plans)
    {
        return $plans
            ->map(function (EsimPlan $plan): array {
                $name = $plan->country_name ?: $plan->region_name ?: $plan->coverage_type;

                return [
                    'name' => $name,
                    'iso' => $plan->country_iso,
                    'price' => (float) $plan->retail_price,
                    'currency' => $plan->currency ?: config('blipblap.currency', 'USD'),
                ];
            })
            ->filter(fn (array $destination): bool => trim((string) $destination['name']) !== '')
            ->groupBy(fn (array $destination): string => (string) $destination['name'])
            ->map(function ($items, string $name): array {
                $iso = collect($items)->pluck('iso')->filter()->first();
                $prices = collect($items)->pluck('price')->filter(fn (float $price): bool => $price > 0);
                $minPrice = $prices->min();
                $currency = collect($items)->pluck('currency')->filter()->first() ?: config('blipblap.currency', 'USD');

                return [
                    'name' => $name,
                    'iso' => $iso ?: strtoupper(substr($name, 0, 2)),
                    'plan_count' => count($items),
                    'min_price' => $minPrice,
                    'currency' => $currency,
                    'flag_url' => $this->flagUrl($iso),
                ];
            })
            ->sort(function (array $a, array $b): int {
                $countCompare = $b['plan_count'] <=> $a['plan_count'];

                if ($countCompare !== 0) {
                    return $countCompare;
                }

                return ((float) ($a['min_price'] ?? PHP_FLOAT_MAX)) <=> ((float) ($b['min_price'] ?? PHP_FLOAT_MAX));
            })
            ->take(9)
            ->values();
    }

    private function isGlobalPlan(EsimPlan $plan): bool
    {
        $name = strtolower((string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type));

        return str_contains($name, 'global') || str_contains($name, 'world');
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
