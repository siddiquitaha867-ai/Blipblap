<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsimOrder extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'order_reference',
        'apply_reference',
        'payment_reference',
        'order_type',
        'bundle_code',
        'iccid',
        'status',
        'validation_status',
        'fulfillment_status',
        'subtotal',
        'total',
        'currency',
        'request_payload',
        'response_payload',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'total' => 'float',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
