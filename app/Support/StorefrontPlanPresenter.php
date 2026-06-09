<?php

namespace App\Support;

use App\Models\EsimPlan;
use Illuminate\Support\Collection;

class StorefrontPlanPresenter
{
    public static function present(EsimPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'supplier_code' => (string) $plan->supplier_code,
            'slug' => (string) $plan->slug,
            'title' => (string) $plan->title,
            'description' => (string) ($plan->description ?? ''),
            'coverage_type' => (string) ($plan->coverage_type ?? ''),
            'country_iso' => $plan->country_iso ? strtoupper((string) $plan->country_iso) : null,
            'country_name' => $plan->country_name,
            'region_name' => $plan->region_name,
            'data_amount' => $plan->data_amount,
            'data_unit' => $plan->data_unit ?: 'GB',
            'duration_days' => $plan->duration_days,
            'unlimited' => (bool) $plan->unlimited,
            'topup_supported' => (bool) $plan->topup_supported,
            'retail_price' => (float) $plan->retail_price,
            'currency' => $plan->currency ?: config('blipblap.currency', 'USD'),
            'is_active' => (bool) $plan->is_active,
            'is_featured' => (bool) $plan->is_featured,
        ];
    }

    public static function collection(Collection $plans): array
    {
        return $plans
            ->map(fn (EsimPlan $plan): array => self::present($plan))
            ->values()
            ->all();
    }
}
