<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ContentPage extends Model
{
    protected static ?bool $tableAvailable = null;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body_html',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

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

    public static function safeLatest(): Collection
    {
        if (! self::tableAvailable()) {
            return collect();
        }

        try {
            return self::query()->latest()->get();
        } catch (QueryException) {
            return collect();
        }
    }

    public function resolveRouteBinding($value, $field = null): Model
    {
        if (! self::tableAvailable()) {
            throw (new ModelNotFoundException())->setModel(static::class, [$value]);
        }

        try {
            return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
        } catch (QueryException) {
            throw (new ModelNotFoundException())->setModel(static::class, [$value]);
        }
    }
}
