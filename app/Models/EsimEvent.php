<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsimEvent extends Model
{
    protected $fillable = [
        'customer_esim_id',
        'esim_order_id',
        'event_type',
        'event_payload',
    ];

    protected $casts = [
        'event_payload' => 'array',
    ];

    public function esim(): BelongsTo
    {
        return $this->belongsTo(CustomerEsim::class, 'customer_esim_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EsimOrder::class, 'esim_order_id');
    }
}
