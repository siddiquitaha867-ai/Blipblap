<?php

namespace Database\Seeders;

use App\Models\EsimPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EsimPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['Pakistan', 'PK', 'Sheesh Pakistan 1GB', 1, 'GB', 3, 4.00],
            ['Pakistan', 'PK', 'Sheesh Pakistan 3GB', 3, 'GB', 7, 7.50],
            ['Pakistan', 'PK', 'Sheesh Pakistan 5GB', 5, 'GB', 30, 10.00],
            ['United Arab Emirates', 'AE', 'UAE Traveler 3GB', 3, 'GB', 7, 9.00],
            ['Saudi Arabia', 'SA', 'Saudi Arabia 5GB', 5, 'GB', 15, 12.00],
            ['United Kingdom', 'GB', 'United Kingdom 10GB', 10, 'GB', 30, 18.00],
        ];

        foreach ($plans as [$country, $iso, $title, $amount, $unit, $days, $price]) {
            EsimPlan::query()->updateOrCreate(
                ['supplier_code' => Str::slug($title, '_')],
                [
                    'slug' => Str::slug($title),
                    'title' => $title,
                    'description' => $title,
                    'coverage_type' => 'local',
                    'country_iso' => $iso,
                    'country_name' => $country,
                    'data_amount' => $amount,
                    'data_unit' => $unit,
                    'duration_days' => $days,
                    'supplier_price' => $price,
                    'retail_price' => $price,
                    'currency' => 'USD',
                    'network_json' => [],
                    'raw_payload' => [],
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );
        }
    }
}
