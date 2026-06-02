<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $fillable = [
        'key',
        'title',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public static function value(string $key, array $default = []): array
    {
        return self::query()->where('key', $key)->value('content') ?: $default;
    }
}
