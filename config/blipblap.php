<?php

return [
    'currency' => env('BLIPBLAP_CURRENCY', 'USD'),
    'markup_percentage' => (float) env('BLIPBLAP_MARKUP_PERCENTAGE', 20),
    'featured_destinations' => [
        ['name' => 'United Arab Emirates', 'iso' => 'AE', 'flag' => '🇦🇪', 'flag_url' => '/images/blipblap/ARE.svg'],
        ['name' => 'Europe', 'iso' => 'EU', 'flag' => '🌐', 'flag_url' => '/images/blipblap/EUR.svg'],
        ['name' => 'Saudi Arabia', 'iso' => 'SA', 'flag' => '🇸🇦', 'flag_url' => '/images/blipblap/SAU.svg'],
        ['name' => 'Russia', 'iso' => 'RU', 'flag' => '🇷🇺', 'flag_url' => '/images/blipblap/RUS.svg'],
        ['name' => 'Oman', 'iso' => 'OM', 'flag' => '🇴🇲', 'flag_url' => '/images/blipblap/OMN.svg'],
        ['name' => 'Egypt', 'iso' => 'EG', 'flag' => '🇪🇬', 'flag_url' => '/images/blipblap/EGY.svg'],
        ['name' => 'United Kingdom', 'iso' => 'GB', 'flag' => '🇬🇧', 'flag_url' => '/images/blipblap/GBR.svg'],
        ['name' => 'Turkey', 'iso' => 'TR', 'flag' => '🇹🇷', 'flag_url' => '/images/blipblap/TUR.svg'],
        ['name' => 'USA', 'iso' => 'US', 'flag' => '🇺🇸', 'flag_url' => '/images/blipblap/USA.svg'],
    ],
];
