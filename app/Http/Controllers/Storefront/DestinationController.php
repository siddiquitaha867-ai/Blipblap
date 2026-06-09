<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use App\Support\StorefrontPlanPresenter;
use Illuminate\Support\Collection;
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
        $searchName = str_replace('-', ' ', $normalizedSlug);

        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedSlug, $name, $iso, $searchName): void {
                $query
                    ->where('country_name', $name)
                    ->orWhere('region_name', $name)
                    ->orWhere('slug', $normalizedSlug)
                    ->orWhere('title', 'like', '%' . $searchName . '%')
                    ->orWhere('country_name', 'like', '%' . $searchName . '%')
                    ->orWhere('region_name', 'like', '%' . $searchName . '%')
                    ->when($iso !== null, fn ($inner) => $inner->orWhere('country_iso', $iso));
            })
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();

        if ($plans->isEmpty()) {
            $plans = $this->plansMatchingGeneratedSlug($normalizedSlug);
        }

        abort_if($plans->isEmpty(), 404);

        return Inertia::render('Storefront/PlanShow', [
            'plan' => StorefrontPlanPresenter::present($plans->first()),
            'relatedPlans' => StorefrontPlanPresenter::collection($plans),
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

    private function plansMatchingGeneratedSlug(string $slug): Collection
    {
        return EsimPlan::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (EsimPlan $plan) use ($slug): bool {
                foreach ([$plan->slug, $plan->country_name, $plan->region_name, $plan->coverage_type, $plan->title] as $value) {
                    if ($value && Str::slug((string) $value) === $slug) {
                        return true;
                    }
                }

                return false;
            })
            ->sortBy([
                ['duration_days', 'asc'],
                ['data_amount', 'asc'],
            ])
            ->values();
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
