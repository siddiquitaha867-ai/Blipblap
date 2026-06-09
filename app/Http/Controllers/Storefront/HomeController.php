<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use App\Models\SiteContent;
use App\Support\StorefrontPlanPresenter;
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
            'content' => SiteContent::value('homepage', []),
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
                'title',
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

        $topNames = [
            'United Arab Emirates',
            'Saudi Arabia',
            'United Kingdom',
            'Pakistan',
            'United States',
            'Canada',
            'Turkey',
            'Europe Extra',
            'Asia',
        ];

        $localPlans = $plans->filter(fn (EsimPlan $plan): bool => $this->isLocalPlan($plan));
        $regionalPlans = $plans->filter(fn (EsimPlan $plan): bool => $this->isRegionalPlan($plan));
        $worldwidePlans = $plans->filter(fn (EsimPlan $plan): bool => $this->isGlobalPlan($plan));

        $top = $this->rankedDestinations($plans, topNames: $topNames);
        $local = $this->rankedDestinations(
            $localPlans->reject(fn (EsimPlan $plan): bool => in_array($this->destinationName($plan), $topNames, true))
        );
        $regional = $this->rankedDestinations(
            $regionalPlans,
            preferredNames: ['Europe Extra', 'Asia', 'Middle East', 'North America', 'LATAM', 'Caribbean', 'Oceania', 'Africa', 'CIS']
        );
        $worldwide = $this->rankedDestinations(
            $worldwidePlans,
            groupByPlan: true,
            preferredNames: ['Global - Light', 'Global - Standard', 'Global - Max', 'Global', 'Global 1GB', 'Global 3GB', 'Global 5GB', 'Global 10GB', 'Global 20GB']
        );

        return [
            'Top Destinations' => $top,
            'Local eSIMs' => $local->isNotEmpty() ? $local : $this->rankedDestinations($localPlans),
            'Regional Packs' => $regional,
            'Worldwide eSIMs' => $worldwide,
        ];
    }

    private function rankedDestinations($plans, array $preferredNames = [], array $topNames = [], bool $groupByPlan = false)
    {
        return $plans
            ->map(function (EsimPlan $plan) use ($groupByPlan): array {
                $name = $groupByPlan ? $this->globalPlanName($plan) : $this->destinationName($plan);

                return [
                    'name' => StorefrontPlanPresenter::text($name),
                    'iso' => StorefrontPlanPresenter::nullableText($plan->country_iso),
                    'price' => (float) $plan->retail_price,
                    'currency' => StorefrontPlanPresenter::text($plan->currency ?: config('blipblap.currency', 'USD')),
                    'icon' => $this->isGlobalPlan($plan) ? 'globe' : null,
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
                    'name' => StorefrontPlanPresenter::text($name),
                    'iso' => StorefrontPlanPresenter::text($iso ?: strtoupper(substr($name, 0, 2))),
                    'plan_count' => count($items),
                    'min_price' => $minPrice,
                    'currency' => StorefrontPlanPresenter::text($currency),
                    'flag_url' => $this->flagUrl($iso),
                    'icon' => collect($items)->pluck('icon')->filter()->first(),
                ];
            })
            ->sort(function (array $a, array $b) use ($preferredNames, $topNames): int {
                $aPreferred = $this->rankIndex((string) $a['name'], $preferredNames);
                $bPreferred = $this->rankIndex((string) $b['name'], $preferredNames);

                if ($aPreferred !== $bPreferred) {
                    return $aPreferred <=> $bPreferred;
                }

                if ($topNames !== []) {
                    $aTop = $this->rankIndex((string) $a['name'], $topNames);
                    $bTop = $this->rankIndex((string) $b['name'], $topNames);

                    if ($aTop !== $bTop) {
                        return $aTop <=> $bTop;
                    }
                }

                $countCompare = $b['plan_count'] <=> $a['plan_count'];

                if ($countCompare !== 0) {
                    return $countCompare;
                }

                return ((float) ($a['min_price'] ?? PHP_FLOAT_MAX)) <=> ((float) ($b['min_price'] ?? PHP_FLOAT_MAX));
            })
            ->take(9)
            ->values();
    }

    private function destinationName(EsimPlan $plan): string
    {
        return (string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type);
    }

    private function globalPlanName(EsimPlan $plan): string
    {
        $name = $this->destinationName($plan);

        if (! in_array(strtolower($name), ['global', 'worldwide'], true)) {
            return $name;
        }

        return (string) ($plan->title ?: $name);
    }

    private function isLocalPlan(EsimPlan $plan): bool
    {
        return $plan->country_name !== null
            && $plan->country_name !== ''
            && ! $this->isGlobalPlan($plan)
            && ($plan->region_name === null || $plan->region_name === '')
            && $plan->coverage_type === 'local';
    }

    private function isRegionalPlan(EsimPlan $plan): bool
    {
        return ! $this->isGlobalPlan($plan)
            && ($plan->region_name !== null && $plan->region_name !== '' || $plan->coverage_type !== 'local');
    }

    private function isGlobalPlan(EsimPlan $plan): bool
    {
        $name = strtolower((string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type));

        return str_contains($name, 'global') || str_contains($name, 'world');
    }

    private function rankIndex(string $name, array $names): int
    {
        $index = array_search($name, $names, true);

        return $index === false ? 1000 : (int) $index;
    }

    private function featuredPlans()
    {
        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('duration_days')
            ->orderBy('retail_price')
            ->get($this->planColumns());

        $featured = $plans
            ->unique(fn (EsimPlan $plan) => strtolower((string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type ?: $plan->title)))
            ->take(6)
            ->values();

        if ($featured->count() >= 6) {
            return $featured;
        }

        $extraPlans = EsimPlan::query()
            ->where('is_active', true)
            ->when($featured->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $featured->pluck('id')))
            ->orderByRaw('COALESCE(country_name, region_name, coverage_type)')
            ->orderBy('duration_days')
            ->orderBy('retail_price')
            ->limit(12)
            ->get($this->planColumns());

        return StorefrontPlanPresenter::collection($featured
            ->concat($extraPlans)
            ->unique(fn (EsimPlan $plan) => strtolower((string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type ?: $plan->title)))
            ->take(6)
            ->values());
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
