<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlansIndexController extends Controller
{
    private const REGIONAL_DESTINATION_NAMES = [
        'africa',
        'asia',
        'balkans',
        'caribbean',
        'central america',
        'europe',
        'latin america',
        'middle east',
        'north america',
        'oceania',
        'south america',
    ];

    public function __invoke(): Response
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

        return Inertia::render('Storefront/PlansIndex', [
            'localPlans' => $this->groupPlans($plans->filter(fn (EsimPlan $plan): bool => $this->isLocalPlan($plan)), 'local'),
            'regionalPlans' => $this->groupPlans($plans->filter(fn (EsimPlan $plan): bool => $this->isRegionalPlan($plan)), 'regional'),
            'globalPlans' => $this->groupPlans($plans->filter(fn (EsimPlan $plan): bool => $this->isGlobalPlan($plan)), 'global'),
        ]);
    }

    private function groupPlans(Collection $plans, string $type): Collection
    {
        return $plans
            ->map(function (EsimPlan $plan) use ($type): array {
                $name = match ($type) {
                    'global' => $this->globalName($plan),
                    'regional' => $this->regionalName($plan),
                    default => (string) $plan->country_name,
                };

                return [
                    'name' => $name,
                    'iso' => $plan->country_iso,
                    'price' => (float) $plan->retail_price,
                    'currency' => $plan->currency ?: config('blipblap.currency', 'USD'),
                ];
            })
            ->filter(fn (array $plan): bool => trim((string) $plan['name']) !== '')
            ->groupBy('name')
            ->map(function (Collection $items, string $name) use ($type): array {
                $iso = strtoupper((string) $items->pluck('iso')->filter()->first());
                $prices = $items->pluck('price')->filter(fn (float $price): bool => $price > 0);

                return [
                    'name' => $name,
                    'plan_count' => $items->count(),
                    'min_price' => $prices->min(),
                    'currency' => $items->pluck('currency')->filter()->first() ?: config('blipblap.currency', 'USD'),
                    'flag_url' => $type === 'local' ? $this->flagUrl($iso) : '',
                    'icon' => $type === 'global' ? 'globe' : null,
                    'url' => '/destinations/' . Str::slug($name),
                ];
            })
            ->sortBy([
                ['name', 'asc'],
            ])
            ->values();
    }

    private function isLocalPlan(EsimPlan $plan): bool
    {
        return ! $this->isGlobalPlan($plan)
            && ! $this->hasRegionalDestinationName($plan)
            && $plan->coverage_type === 'local'
            && filled($plan->country_name);
    }

    private function isRegionalPlan(EsimPlan $plan): bool
    {
        return ! $this->isGlobalPlan($plan)
            && $this->hasRegionalDestinationName($plan);
    }

    private function isGlobalPlan(EsimPlan $plan): bool
    {
        $name = strtolower((string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type ?: $plan->title));

        return str_contains($name, 'global') || str_contains($name, 'world');
    }

    private function globalName(EsimPlan $plan): string
    {
        $name = (string) ($plan->country_name ?: $plan->region_name ?: $plan->coverage_type);

        if (in_array(strtolower($name), ['global', 'worldwide'], true)) {
            return (string) ($plan->title ?: $name);
        }

        return $name ?: (string) $plan->title;
    }

    private function regionalName(EsimPlan $plan): string
    {
        foreach ([$plan->country_name, $plan->region_name, $plan->coverage_type, $plan->title] as $name) {
            $normalizedName = $this->normalizedName($name);

            if ($this->isRegionalDestinationName($normalizedName)) {
                return Str::title($normalizedName);
            }
        }

        return (string) ($plan->region_name ?: $plan->country_name ?: $plan->coverage_type);
    }

    private function hasRegionalDestinationName(EsimPlan $plan): bool
    {
        foreach ([$plan->country_name, $plan->region_name, $plan->coverage_type, $plan->title] as $name) {
            if ($this->isRegionalDestinationName($this->normalizedName($name))) {
                return true;
            }
        }

        return false;
    }

    private function isRegionalDestinationName(string $name): bool
    {
        return in_array($name, self::REGIONAL_DESTINATION_NAMES, true);
    }

    private function normalizedName(?string $name): string
    {
        return trim(strtolower((string) $name));
    }

    private function flagUrl(string $iso): string
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

        if ($iso === '') {
            return '';
        }

        return $localFlagMap[$iso] ?? (strlen($iso) === 2 ? 'https://flagcdn.com/w80/' . strtolower($iso) . '.png' : '');
    }
}
