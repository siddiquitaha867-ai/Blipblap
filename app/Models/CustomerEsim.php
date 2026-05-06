<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEsim extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'iccid',
        'nickname',
        'current_bundle_code',
        'status',
        'matching_id',
        'smdp_address',
        'activation_code',
        'qr_code_url',
        'install_details',
        'last_status',
        'topup_supported',
        'expires_at',
        'last_synced_at',
        'source_order_id',
    ];

    protected $casts = [
        'install_details' => 'array',
        'last_status' => 'array',
        'topup_supported' => 'boolean',
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EsimOrder::class, 'source_order_id');
    }
}
