<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignEvent extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'esim_order_id',
        'event_type',
        'event_status',
        'event_payload',
    ];

    protected $casts = [
        'event_payload' => 'array',
    ];
}
