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
        $normalizedSlug = preg_replace('/-esim$/', '', $slug) ?: $slug;
        $names = $this->namesForSlug($normalizedSlug);
        $iso = $this->isoForSlug($normalizedSlug);
        $searchTerms = $this->searchTermsForSlug($normalizedSlug, $names);

        $plans = EsimPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($names, $searchTerms, $iso) {
                $query->whereIn('country_name', $names)
                    ->orWhereIn('region_name', $names);

                foreach ($searchTerms as $term) {
                    $query
                        ->orWhere('country_name', 'like', '%' . $term . '%')
                        ->orWhere('region_name', 'like', '%' . $term . '%');
                }

                $query->when($iso !== null, fn ($inner) => $inner->orWhere('country_iso', $iso));
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
            'tanzania-united-republic-of' => 'Tanzania',
        ][$slug] ?? Str::of($slug)->replace('-', ' ')->title()->toString();
    }

    /**
     * Supplier catalogue names sometimes use official country labels such as
     * "Tanzania, United Republic of" while public URLs use a plain slug.
     */
    private function namesForSlug(string $slug): array
    {
        $names = [
            $this->nameForSlug($slug),
            Str::of($slug)->replace('-', ' ')->title()->toString(),
        ];

        $names = array_merge(
            $names,
            $this->trailingDescriptorNames($slug, 'united-republic-of', 'United Republic of'),
            $this->trailingDescriptorNames($slug, 'republic-of', 'Republic of'),
            $this->trailingDescriptorNames($slug, 'islamic-republic-of', 'Islamic Republic of'),
        );

        return collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function trailingDescriptorNames(string $slug, string $suffix, string $descriptor): array
    {
        if (! str_ends_with($slug, '-' . $suffix)) {
            return [];
        }

        $baseSlug = Str::beforeLast($slug, '-' . $suffix);
        $baseName = Str::of($baseSlug)->replace('-', ' ')->title()->toString();

        return [
            $baseName,
            $baseName . ', ' . $descriptor,
            $descriptor . ' ' . $baseName,
        ];
    }

    private function searchTermsForSlug(string $slug, array $names): array
    {
        $terms = array_merge([str_replace('-', ' ', $slug)], $names);

        foreach (['united-republic-of', 'republic-of', 'islamic-republic-of'] as $suffix) {
            if (str_ends_with($slug, '-' . $suffix)) {
                $terms[] = str_replace('-', ' ', Str::beforeLast($slug, '-' . $suffix));
            }
        }

        return collect($terms)
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => $term !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function isoForSlug(string $slug): ?string
    {
        return [
            'pakistan' => 'PK',
            'tanzania' => 'TZ',
            'tanzania-united-republic-of' => 'TZ',
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
