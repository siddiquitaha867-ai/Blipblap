<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class SiteContent extends Model
{
    protected static ?bool $tableAvailable = null;

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
        if (! self::tableAvailable()) {
            return $default;
        }

        try {
            return self::query()->where('key', $key)->value('content') ?: $default;
        } catch (QueryException) {
            return $default;
        }
    }

    public static function storeValue(string $key, string $title, array $content): bool
    {
        if (! self::tableAvailable()) {
            return false;
        }

        try {
            self::query()->updateOrCreate(
                ['key' => $key],
                ['title' => $title, 'content' => $content],
            );

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    public static function tableAvailable(): bool
    {
        if (self::$tableAvailable !== null) {
            return self::$tableAvailable;
        }

        try {
            return self::$tableAvailable = Schema::hasTable((new static)->getTable());
        } catch (\Throwable) {
            return self::$tableAvailable = false;
        }
    }
}
