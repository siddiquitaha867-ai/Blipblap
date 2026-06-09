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
            'supplier_code' => self::text($plan->supplier_code),
            'slug' => self::text($plan->slug),
            'title' => self::text($plan->title),
            'description' => self::text($plan->description),
            'coverage_type' => self::text($plan->coverage_type),
            'country_iso' => $plan->country_iso ? strtoupper(self::text($plan->country_iso)) : null,
            'country_name' => self::nullableText($plan->country_name),
            'region_name' => self::nullableText($plan->region_name),
            'data_amount' => $plan->data_amount,
            'data_unit' => self::text($plan->data_unit ?: 'GB'),
            'duration_days' => $plan->duration_days,
            'unlimited' => (bool) $plan->unlimited,
            'topup_supported' => (bool) $plan->topup_supported,
            'retail_price' => (float) $plan->retail_price,
            'currency' => self::text($plan->currency ?: config('blipblap.currency', 'USD')),
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

    public static function nullableText(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::text($value);
    }

    public static function text(mixed $value): string
    {
        $text = (string) ($value ?? '');

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? '';
    }
}
