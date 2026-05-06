<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyEvent extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'esim_order_id',
        'customer_esim_id',
        'points',
        'event_type',
        'event_status',
        'event_reference',
        'event_payload',
    ];

    protected $casts = [
        'event_payload' => 'array',
        'points' => 'integer',
    ];
}
