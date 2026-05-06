<?php

namespace App\Services\EsimGo;

use Illuminate\Support\Str;

class CatalogueMapper
{
    public function map(array $bundle): array
    {
        $name = (string) ($bundle['name'] ?? $bundle['bundle'] ?? $bundle['id'] ?? '');
        $description = (string) ($bundle['description'] ?? $name);
        $countries = $bundle['countries'] ?? [];
        $firstCountry = is_array($countries) ? ($countries[0] ?? []) : [];
        $dataAmount = $bundle['dataAmount'] ?? $bundle['data_amount'] ?? $bundle['initialQuantity'] ?? null;

        return [
            'supplier_code' => $name,
            'slug' => Str::slug($name ?: $description),
            'title' => $this->titleFromDescription($description, $name),
            'description' => $description,
            'coverage_type' => $this->coverageType($bundle),
            'country_iso' => is_array($firstCountry) ? ($firstCountry['iso'] ?? $firstCountry['countryCode'] ?? null) : null,
            'country_name' => is_array($firstCountry) ? ($firstCountry['name'] ?? $firstCountry['country'] ?? null) : null,
            'region_name' => $bundle['region'] ?? $bundle['regionName'] ?? null,
            'data_amount' => is_numeric($dataAmount) ? (float) $dataAmount : null,
            'data_unit' => $bundle['dataUnit'] ?? $bundle['unit'] ?? 'MB',
            'duration_days' => (int) ($bundle['duration'] ?? $bundle['durationDays'] ?? $bundle['validity'] ?? 0),
            'unlimited' => (bool) ($bundle['unlimited'] ?? false),
            'supplier_price' => (float) ($bundle['price'] ?? $bundle['cost'] ?? 0),
            'retail_price' => $this->retailPrice((float) ($bundle['price'] ?? $bundle['cost'] ?? 0)),
            'currency' => config('blipblap.currency', 'USD'),
            'network_json' => $bundle['networks'] ?? [],
            'raw_payload' => $bundle,
        ];
    }

    private function titleFromDescription(string $description, string $fallback): string
    {
        return trim($description) !== '' ? trim($description) : $fallback;
    }

    private function coverageType(array $bundle): string
    {
        if (! empty($bundle['region']) || ! empty($bundle['regionName'])) {
            return 'regional';
        }

        $countries = $bundle['countries'] ?? [];

        if (is_array($countries) && count($countries) > 1) {
            return 'regional';
        }

        return 'local';
    }

    private function retailPrice(float $supplierPrice): float
    {
        $markup = (float) config('blipblap.markup_percentage', 20);
        $price = $supplierPrice + ($supplierPrice * ($markup / 100));

        return round($price, 2);
    }
}
