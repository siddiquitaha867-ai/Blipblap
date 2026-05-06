<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyBalance extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'points_balance',
        'lifetime_points_earned',
        'lifetime_points_redeemed',
        'last_event_at',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'lifetime_points_earned' => 'integer',
        'lifetime_points_redeemed' => 'integer',
        'last_event_at' => 'datetime',
    ];
}
