<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsimPlan extends Model
{
    protected $fillable = [
        'supplier_code',
        'slug',
        'title',
        'description',
        'coverage_type',
        'country_iso',
        'country_name',
        'region_name',
        'data_amount',
        'data_unit',
        'duration_days',
        'unlimited',
        'topup_supported',
        'supplier_price',
        'retail_price',
        'tax_amount',
        'currency',
        'network_json',
        'raw_payload',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'data_amount' => 'float',
        'duration_days' => 'integer',
        'unlimited' => 'boolean',
        'topup_supported' => 'boolean',
        'supplier_price' => 'float',
        'retail_price' => 'float',
        'tax_amount' => 'float',
        'network_json' => 'array',
        'raw_payload' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
}
