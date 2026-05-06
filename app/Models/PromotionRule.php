<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    protected $fillable = [
        'title',
        'rule_type',
        'is_active',
        'conditions',
        'actions',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'actions' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
